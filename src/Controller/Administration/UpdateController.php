<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Exception;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\SetupHelper;
use Mosparo\Helper\UpdateHelper;
use Mosparo\Message\UpdateMessage;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/administration/update")
 */
class UpdateController extends AbstractController
{
    protected KernelInterface $kernel;

    protected SetupHelper $setupHelper;

    protected UpdateHelper $updateHelper;

    protected ConfigHelper $configHelper;

    protected TranslatorInterface $translator;

    protected string $mosparoVersion;

    public function __construct(KernelInterface $kernel, SetupHelper $setupHelper, UpdateHelper $updateHelper, ConfigHelper $configHelper, TranslatorInterface $translator, string $mosparoVersion)
    {
        $this->kernel = $kernel;
        $this->setupHelper = $setupHelper;
        $this->updateHelper = $updateHelper;
        $this->configHelper = $configHelper;
        $this->translator = $translator;
        $this->mosparoVersion = $mosparoVersion;
    }

    /**
     * @Route("/", name="administration_update_overview")
     */
    public function overview(Request $request): Response
    {
        $updateChannel = $this->configHelper->getEnvironmentConfigValue('updateChannel', 'stable');
        $config = [
            'updateChannel' => $updateChannel,
        ];
        $channels = [
            'administration.update.settings.form.channelStable' => 'stable',
            'administration.update.settings.form.channelDevelop' => 'develop',
        ];
        $settingsForm = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('updateChannel', ChoiceType::class, ['label' => 'administration.update.updateChannel', 'required' => true, 'choices' => $channels, 'attr' => ['class' => 'form-select']])
            ->getForm();

        $settingsForm->handleRequest($request);
        if ($settingsForm->isSubmitted() && $settingsForm->isValid()) {
            $this->configHelper->writeEnvironmentConfig([
                'updateChannel' => $settingsForm->get('updateChannel')->getData(),
            ]);

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.update.settings.message.savedSuccessfully',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_update_overview');
        }


        $checkedForUpdates = $request->query->has('checkedForUpdates');
        $session = $request->getSession();
        $isUpdateAvailable = $session->get('isUpdateAvailable', false);
        $availableUpdateData = $session->get('availableUpdateData', []);

        $translatedChannels = array_flip($channels);
        return $this->render('administration/update/overview.html.twig', [
            'mosparoVersion' => $this->mosparoVersion,
            'updateChannel' => $translatedChannels[$updateChannel],
            'settingsForm' => $settingsForm->createView(),
            'checkedForUpdates' => $checkedForUpdates,
            'isUpdateAvailable' => $isUpdateAvailable,
            'availableUpdateData' => $availableUpdateData,
        ]);
    }

    /**
     * @Route("/check", name="administration_update_check")
     */
    public function check(Request $request): Response
    {
        $session = $request->getSession();

        try {
            $this->updateHelper->checkForUpdates();
        } catch (Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'administration.update.check.message.errorCheckingForUpdates',
                ['%errorMessage%' => $e->getMessage()],
                'mosparo'
            ));

            return $this->redirectToRoute('administration_update_overview');
        }

        $session->set('isUpdateAvailable', $this->updateHelper->isUpdateAvailable());
        $session->set('availableUpdateData', $this->updateHelper->getAvailableUpdateData());

        return $this->redirectToRoute('administration_update_overview', ['checkedForUpdates' => 1]);
    }

    /**
     * @Route("/execute", name="administration_update_execute")
     */
    public function execute(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->has('isUpdateAvailable')) {
            return $this->redirectToRoute('administration_update_overview');
        }

        $availableUpdateData = $session->get('availableUpdateData', []);
        return $this->render('administration/update/execute.html.twig', [
            'mosparoVersion' => $this->mosparoVersion,
            'availableUpdateData' => $availableUpdateData,
        ]);
    }

    /**
     * @Route("/execute/update", name="administration_update_execute_update")
     */
    public function executeUpdate(Request $request)
    {
        $this->updateHelper->setOutputHandler(function (UpdateMessage $message) {
            echo json_encode([
                'inProgress' => $message->isInProgress(),
                'error' => $message->isError(),
                'completed' => $message->isCompleted(),
                'message' => $message->getMessage(),
            ]) . PHP_EOL;

            // Add 4kb of spaces to force the output buffer to clean
            echo str_pad('', 4096, ' ');

            ob_flush();
            flush();
        });

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request) {
            $session = $request->getSession();
            if (!$session->has('isUpdateAvailable')) {
                $this->updateHelper->output(new UpdateMessage('general', UpdateMessage::STATUS_ERROR, 'No update data found.'));
                return;
            }

            $versionData = $session->get('availableUpdateData');

            try {
                $result = $this->updateHelper->updateMosparo($versionData);
            } catch (\Exception $e) {
                $this->updateHelper->output(new UpdateMessage('error', UpdateMessage::STATUS_ERROR, $e->getMessage()));
                return;
            }

            if ($result) {
                $this->updateHelper->output(new UpdateMessage('general', UpdateMessage::STATUS_COMPLETED, 'Completed'));
            }
        });

        $response->headers->set('X-Accel-Buffering', 'no');
        $response->send();
    }

    /**
     * @Route("/finalize", name="administration_update_finalize")
     */
    public function finalize(): Response
    {
        // Prepare database and execute the migrations
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'doctrine:migrations:migrate',
            '-n'
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);
        $output->fetch();

        // Update the installed version
        $this->configHelper->writeEnvironmentConfig([
            'mosparo_installed_version' => $this->mosparoVersion,
        ]);

        // Clear the cache after the upgrade
        $input = new ArrayInput(array(
            'command' => 'cache:clear',
            '-n'
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);
        $output->fetch();

        return $this->render('administration/update/finalize.html.twig');
    }
}