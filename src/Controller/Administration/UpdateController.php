<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Exception;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\ConnectionHelper;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\Migration\V14OptimizeRulesHelper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\SetupHelper;
use Mosparo\Helper\UpdateHelper;
use Mosparo\Kernel;
use Mosparo\Message\UpdateMessage;
use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/administration/update')]
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

    #[Route('/', name: 'administration_update_overview')]
    public function overview(Request $request): Response
    {
        $updateChannel = $this->configHelper->getEnvironmentConfigValue('updateChannel', 'stable');
        $config = [
            'updateChannel' => $updateChannel,
        ];
        $channels = [
            'administration.update.settings.form.channelStable' => 'stable',
            'administration.update.settings.form.channelBeta' => 'beta',
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

        $isUpgradeAvailable = $this->updateHelper->isUpgradeAvailable();
        $availableUpgradeData = $this->updateHelper->getAvailableUpgradeData();

        $translatedChannels = array_flip($channels);
        return $this->render('administration/update/overview.html.twig', [
            'mosparoMajorVersion' => Kernel::MAJOR_VERSION,
            'mosparoVersion' => Kernel::VERSION,

            'updateChannel' => $translatedChannels[$updateChannel],
            'settingsForm' => $settingsForm->createView(),
            'checkedForUpdates' => $checkedForUpdates,
            'checkedAt' => $checkedAt,

            'isUpdateAvailable' => $isUpdateAvailable,
            'availableUpdateData' => $availableUpdateData,

            'isUpgradeAvailable' => $isUpgradeAvailable,
            'availableUpgradeData' => $availableUpgradeData,

            'updatesEnabled' => $this->updatesEnabled,
            'downloadCheck' => $this->connectionHelper->checkIfDownloadIsPossible(),
        ]);
    }

    #[Route('/check', name: 'administration_update_check')]
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

    #[Route('/upgrade/check-requirements', name: 'administration_upgrade_check_requirements')]
    public function upgradeCheckRequirements(Request $request): Response
    {
        $isUpgradeAvailable = $this->updateHelper->isUpgradeAvailable();
        $availableUpgradeData = $this->updateHelper->getAvailableUpgradeData();

        if (!$isUpgradeAvailable || !$this->updatesEnabled) {
            return $this->redirectToRoute('administration_update_overview');
        }

        [ $meetPrerequisites, $prerequisites ] = $this->setupHelper->checkUpgradePrerequisites($availableUpgradeData['majorVersionData'] ?? []);

        return $this->render('administration/update/check_requirements.html.twig', [
            'mosparoMajorVersion' => Kernel::MAJOR_VERSION,

            'isUpgradeAvailable' => $isUpgradeAvailable,
            'availableUpgradeData' => $availableUpgradeData,

            'meetPrerequisites' => $meetPrerequisites,
            'prerequisites' => $prerequisites,

            'updatesEnabled' => $this->updatesEnabled,
        ]);
    }

    #[Route('/upgrade/execute', name: 'administration_upgrade_execute')]
    public function executeUpgrade(Request $request): Response
    {
        $session = $request->getSession();

        $isUpgradeAvailable = $this->updateHelper->isUpgradeAvailable();
        $availableUpgradeData = $this->updateHelper->getAvailableUpgradeData();

        $availableUpdateData = $availableUpgradeData['versionData'] ?? false;

        if (!$isUpgradeAvailable || !$this->updatesEnabled || !$availableUpdateData) {
            return $this->redirectToRoute('administration_update_overview');
        }

        $session->set('upgradeMosparo', true);

        return $this->redirectToRoute('administration_update_execute');
    }

    #[Route('/execute', name: 'administration_update_execute')]
    public function execute(Request $request): Response
    {
        $session = $request->getSession();

        [$isUpdateAvailable, $availableUpdateData] = $this->getAvailableUpdateData($session);
        if (!$isUpdateAvailable || !$this->updatesEnabled) {
            return $this->redirectToRoute('administration_update_overview');
        }

        [$temporaryLogFilePath, $temporaryLogFileUrl] = $this->updateHelper->defineTemporaryLogFile();
        $session->set('temporaryLogFile', $temporaryLogFilePath);

        // Abort, if this version is already installed.
        if ($availableUpdateData['number'] == Kernel::VERSION) {
            return $this->redirectToRoute('administration_update_overview');
        }

        return $this->render('administration/update/execute.html.twig', [
            'mosparoVersion' => Kernel::VERSION,
            'availableUpdateData' => $availableUpdateData,
            'temporaryLogFileUrl' => $temporaryLogFileUrl,
        ]);
    }

    #[Route('/execute/update', name: 'administration_update_execute_update')]
    public function executeUpdate(Request $request)
    {
        $session = $request->getSession();
        [$isUpdateAvailable, $versionData] = $this->getAvailableUpdateData($session);
        if (!$isUpdateAvailable || !$this->updatesEnabled) {
            $this->updateHelper->output(new UpdateMessage('general', UpdateMessage::STATUS_ERROR, 'No update data found.'));
            return new JsonResponse(['error' => true, 'errorMessage' => 'No update data found.']);
        }

        $temporaryLogFile = $session->get('temporaryLogFile', null);
        if ($temporaryLogFile === null) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No temporary log file defined.']);
        }

        // Abort, if this version is already installed.
        if ($versionData['number'] == Kernel::VERSION) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Version already installed.']);
        }

        // Without this empty and never used response header bag, it's possible that depending
        // on the operating system (for example, Windows/IIS) the class is not available after
        // the update. Creating an object here will load the class into the cache before the
        // update is executed.
        $responseHeaderBag = new ResponseHeaderBag();

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

    #[Route('/finalize', name: 'administration_update_finalize')]
    public function finalize(Request $request, Filesystem $filesystem): Response
    {
        $oldInstalledVersion = $this->configHelper->getEnvironmentConfigValue('mosparo_installed_version');
        if ($oldInstalledVersion === Kernel::VERSION) {
            return $this->redirectToRoute('dashboard');
        }

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

        $tokenGenerator = new TokenGenerator();

        // Update the installed version
        $this->configHelper->writeEnvironmentConfig([
            'mosparo_installed_version' => Kernel::VERSION,
            'mosparo_assets_version' => $tokenGenerator->generateShortToken(),
            'mosparo_finalize_update' => true,
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

        if ($session->has('upgradeMosparo')) {
            $session->remove('upgradeMosparo');
        }

        return $this->redirectToRoute('administration_update_finalize_process', ['oldVersion' => $oldInstalledVersion]);
    }

    #[Route('/finalize/process', name: 'administration_update_finalize_process')]
    public function finalizeProcess(Request $request, ProjectHelper $projectHelper, V14OptimizeRulesHelper $v14OptimizeRulesHelper)
    {
        if (!$this->configHelper->getEnvironmentConfigValue('mosparo_finalize_update')) {
            return $this->redirectToRoute('dashboard');
        }

        $projectHelper->unsetActiveProject();

        // Process the rules for version 1.4
        if ($v14OptimizeRulesHelper->hasOpenTasks()) {
            return $this->redirectToRoute('administration_update_finalize_process_v1_4', ['oldVersion' => $request->query->get('oldVersion')]);
        }

        return $this->redirectToRoute('administration_update_finalized', ['oldVersion' => $request->query->get('oldVersion')]);
    }

    #[Route('/finalize/process/v1.4', name: 'administration_update_finalize_process_v1_4')]
    public function finalizeProcessV14(Request $request, ProjectHelper $projectHelper, V14OptimizeRulesHelper $v14OptimizeRulesHelper)
    {
        if (!$this->configHelper->getEnvironmentConfigValue('mosparo_finalize_update')) {
            return $this->redirectToRoute('dashboard');
        }

        $projectHelper->unsetActiveProject();

        if ($request->request->has('process') && $request->request->get('process')) {
            $submittedToken = $request->request->get('token');
            if (!$this->isCsrfTokenValid('process-tasks', $submittedToken)) {
                return new Response(null, 401);
            }

            $numberOfProcessedTasks = $v14OptimizeRulesHelper->processOpenTasks();

            if ($numberOfProcessedTasks === 0) {
                return new JsonResponse([
                    'finished' => true,
                ]);
            }

            return new JsonResponse([
                'processedTasks' => $numberOfProcessedTasks,
            ]);
        }

        return $this->render('administration/update/process_v1_4.html.twig', [
            'numberOfTasks' => $v14OptimizeRulesHelper->countOpenTasks(),
            'urlAfterProcess' => $this->generateUrl('administration_update_finalize_process', ['oldVersion' => $request->query->get('oldVersion')]),
        ]);
    }

    #[Route('/finalized', name: 'administration_update_finalized')]
    public function finalized(Request $request): Response
    {
        if (!$this->configHelper->getEnvironmentConfigValue('mosparo_finalize_update')) {
            return $this->redirectToRoute('dashboard');
        }

        $this->configHelper->writeEnvironmentConfig([
            'mosparo_finalize_update' => false,
        ]);

        $oldInstalledVersion = $request->query->get('oldVersion');
        $showGeoIp2Info = false;
        if ($this->configHelper->getEnvironmentConfigValue('geoipActive', false)) {
            $showGeoIp2Info = (
                version_compare($oldInstalledVersion, '1.2.2', '<') ||
                !$this->configHelper->getEnvironmentConfigValue('geoipAccountId', '')
            );
        }

        return $this->render('administration/update/finalized.html.twig', [
            'showHostsAlert' => version_compare($oldInstalledVersion, '1.2.0', '<'),
            'showGeoIp2Info' => $showGeoIp2Info,
        ]);
    }

    protected function getAvailableUpdateData(Session $session)
    {
        $isUpdateAvailable = $this->updateHelper->isUpdateAvailable();
        $availableUpdateData = $this->updateHelper->getAvailableUpdateData();
        if ($session->has('upgradeMosparo') && $session->get('upgradeMosparo') && $this->updateHelper->isUpgradeAvailable()) {
            $isUpdateAvailable = true;
            $availableUpdateData = $this->updateHelper->getAvailableUpgradeData()['versionData'];
        }

        return [$isUpdateAvailable, $availableUpdateData];
    }
}