<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Exception;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\ConnectionHelper;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\SetupHelper;
use Mosparo\Helper\UpdateHelper;
use Mosparo\Kernel;
use Mosparo\Message\UpdateMessage;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/administration/update")
 */
class UpdateController extends AbstractController
{
    protected KernelInterface $kernel;

    protected SetupHelper $setupHelper;

    protected UpdateHelper $updateHelper;

    protected ConnectionHelper $connectionHelper;

    protected ConfigHelper $configHelper;

    protected DesignHelper $designHelper;

    protected TranslatorInterface $translator;

    protected bool $updatesEnabled;

    public function __construct(
        KernelInterface $kernel,
        SetupHelper $setupHelper,
        UpdateHelper $updateHelper,
        ConnectionHelper $connectionHelper,
        ConfigHelper $configHelper,
        DesignHelper $designHelper,
        TranslatorInterface $translator,
        bool $updatesEnabled
    ) {
        $this->kernel = $kernel;
        $this->setupHelper = $setupHelper;
        $this->updateHelper = $updateHelper;
        $this->connectionHelper = $connectionHelper;
        $this->configHelper = $configHelper;
        $this->designHelper = $designHelper;
        $this->translator = $translator;
        $this->updatesEnabled = $updatesEnabled;
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
        $attr = [
            'class' => 'form-select',
        ];
        if (!$this->updatesEnabled) {
            $attr['disabled'] = 'disabled';
        }
        $settingsForm = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('updateChannel', ChoiceType::class, [
                'label' => 'administration.update.updateChannel',
                'required' => true,
                'choices' => $channels,
                'attr' => $attr
            ])
            ->getForm();

        $settingsForm->handleRequest($request);
        if ($settingsForm->isSubmitted() && $settingsForm->isValid() && $this->updatesEnabled) {
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

        $checkedForUpdates = $this->updateHelper->hasCachedData();
        $checkedAt = $this->updateHelper->getCheckedAt();
        $isUpdateAvailable = $this->updateHelper->isUpdateAvailable();
        $availableUpdateData = $this->updateHelper->getAvailableUpdateData();

        $translatedChannels = array_flip($channels);
        return $this->render('administration/update/overview.html.twig', [
            'mosparoVersion' => Kernel::VERSION,
            'updateChannel' => $translatedChannels[$updateChannel],
            'settingsForm' => $settingsForm->createView(),
            'checkedForUpdates' => $checkedForUpdates,
            'checkedAt' => $checkedAt,
            'isUpdateAvailable' => $isUpdateAvailable,
            'availableUpdateData' => $availableUpdateData,
            'updatesEnabled' => $this->updatesEnabled,
            'downloadCheck' => $this->connectionHelper->checkIfDownloadIsPossible(),
        ]);
    }

    /**
     * @Route("/check", name="administration_update_check")
     */
    public function check(Request $request): Response
    {
        try {
            $this->updateHelper->getCachedUpdateData(true);
        } catch (Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'administration.update.check.message.errorCheckingForUpdates',
                ['%errorMessage%' => $e->getMessage()],
                'mosparo'
            ));

            return $this->redirectToRoute('administration_update_overview');
        }

        return $this->redirectToRoute('administration_update_overview', ['checkedForUpdates' => 1]);
    }

    /**
     * @Route("/execute", name="administration_update_execute")
     */
    public function execute(Request $request): Response
    {
        $session = $request->getSession();

        if (!$this->updateHelper->isUpdateAvailable() || !$this->updatesEnabled) {
            return $this->redirectToRoute('administration_update_overview');
        }

        [$temporaryLogFilePath, $temporaryLogFileUrl] = $this->updateHelper->defineTemporaryLogFile();
        $session->set('temporaryLogFile', $temporaryLogFilePath);

        $availableUpdateData = $this->updateHelper->getAvailableUpdateData();

        // Abort, if this version is already installed.
        if ($availableUpdateData['version'] == Kernel::VERSION) {
            return $this->redirectToRoute('administration_update_overview');
        }

        return $this->render('administration/update/execute.html.twig', [
            'mosparoVersion' => Kernel::VERSION,
            'availableUpdateData' => $availableUpdateData,
            'temporaryLogFileUrl' => $temporaryLogFileUrl,
        ]);
    }

    /**
     * @Route("/execute/update", name="administration_update_execute_update")
     */
    public function executeUpdate(Request $request)
    {
        $session = $request->getSession();
        if (!$this->updateHelper->isUpdateAvailable() || !$this->updatesEnabled) {
            $this->updateHelper->output(new UpdateMessage('general', UpdateMessage::STATUS_ERROR, 'No update data found.'));
            return new JsonResponse(['error' => true, 'errorMessage' => 'No update data found.']);
        }

        $versionData = $this->updateHelper->getAvailableUpdateData();

        $temporaryLogFile = $session->get('temporaryLogFile', null);
        if ($temporaryLogFile === null) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No temporary log file defined.']);
        }

        // Abort, if this version is already installed.
        if ($versionData['version'] == Kernel::VERSION) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Version already installed.']);
        }

        $this->updateHelper->setOutputHandler(function (UpdateMessage $message) use ($temporaryLogFile) {
            $message = json_encode([
                    'timestamp' => $message->getDateTime()->getTimestamp(),
                    'inProgress' => $message->isInProgress(),
                    'error' => $message->isError(),
                    'completed' => $message->isCompleted(),
                    'message' => $message->getMessage(),
                ]) . PHP_EOL;

            $flag = (file_exists($temporaryLogFile)) ? FILE_APPEND : 0;
            file_put_contents($temporaryLogFile, $message, $flag);
        });

        try {
            $result = $this->updateHelper->updateMosparo($versionData);
        } catch (\Exception $e) {
            $this->updateHelper->output(new UpdateMessage('error', UpdateMessage::STATUS_ERROR, $e->getMessage()));
            return new JsonResponse(['error' => true, 'errorMessage' => $e->getMessage()]);
        }

        if ($result) {
            $this->updateHelper->output(new UpdateMessage('general', UpdateMessage::STATUS_COMPLETED, 'Completed'));

            return new JsonResponse(['result' => true]);
        }

        return new JsonResponse(['error' => true, 'errorMessage' => 'An unknown error occurred.']);
    }

    /**
     * @Route("/finalize", name="administration_update_finalize")
     */
    public function finalize(Request $request, Filesystem $filesystem): Response
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
            'mosparo_installed_version' => Kernel::VERSION,
        ]);

        // Clear the cache after the upgrade
        $input = new ArrayInput(array(
            'command' => 'cache:clear',
            '-n'
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);
        $output->fetch();

        // Refresh the frontend resources
        $this->designHelper->refreshFrontendResourcesForAllProjects();

        // Remove the temporary log file
        $session = $request->getSession();
        if ($session->has('temporaryLogFile')) {
            $filesystem->remove($session->get('temporaryLogFile'));
        }

        return $this->render('administration/update/finalize.html.twig');
    }
}