<?php

namespace Mosparo\Controller;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Exception\AdminUserAlreadyExistsException;
use Mosparo\Exception\UserAlreadyExistsException;
use Mosparo\Form\PasswordFormType;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\ConnectionHelper;
use Mosparo\Helper\SetupHelper;
use Mosparo\Kernel;
use Mosparo\Util\TokenGenerator;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Regex;

#[Route('/setup')]
class SetupController extends AbstractController
{
    protected KernelInterface $kernel;

    protected SetupHelper $setupHelper;

    protected ConfigHelper $configHelper;

    protected ConnectionHelper $connectionHelper;

    public function __construct(KernelInterface $kernel, SetupHelper $setupHelper, ConfigHelper $configHelper, ConnectionHelper $connectionHelper)
    {
        $this->kernel = $kernel;
        $this->setupHelper = $setupHelper;
        $this->configHelper = $configHelper;
        $this->connectionHelper = $connectionHelper;
    }

    #[Route('/', name: 'setup_start')]
    public function start(): Response
    {
        if ($this->setupHelper->isInstalled()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('setup/start.html.twig');
    }

    #[Route('/prerequisites', name: 'setup_prerequisites')]
    public function prerequisites(): Response
    {
        if ($this->setupHelper->isInstalled()) {
            return $this->redirectToRoute('dashboard');
        }

        [ $meetPrerequisites, $prerequisites ] = $this->setupHelper->checkPrerequisites();

        return $this->render('setup/prerequisites.html.twig', [
            'meetPrerequisites' => $meetPrerequisites,
            'prerequisites' => $prerequisites,
            'downloadCheck' => $this->connectionHelper->checkIfDownloadIsPossible(),
        ]);
    }

    #[Route('/database', name: 'setup_database')]
    public function database(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->setupHelper->isInstalled()) {
            return $this->redirectToRoute('dashboard');
        }

        $databaseSystems = [];

        if ($this->setupHelper->hasPhpExtension('pdo_mysql')) {
            $databaseSystems['setup.database.system.mysql'] = 'mysql';
        }

        if ($this->setupHelper->hasPhpExtension('pdo_pgsql')) {
            $databaseSystems['setup.database.system.postgres'] = 'postgres';
        }

        if ($this->setupHelper->hasPhpExtension('pdo_sqlite')) {
            $databaseSystems['setup.database.system.sqlite'] = 'sqlite';
        }

        $form = $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->add('system', ChoiceType::class, [
                'label' => 'setup.database.form.system',
                'choices' => $databaseSystems,
                'placeholder' => 'setup.database.system.pleaseChoose'])
            ->add('host', TextType::class, ['label' => 'setup.database.form.host'])
            ->add('port', TextType::class, ['label' => 'setup.database.form.port', 'required' => false, 'attr' => ['placeholder' => 'setup.database.port.default']])
            ->add('database', TextType::class, ['label' => 'setup.database.form.database'])
            ->add('user', TextType::class, ['label' => 'setup.database.form.user'])
            ->add('password', PasswordType::class, ['label' => 'setup.database.form.password', 'attr' => ['autocomplete' => 'off']])
            ->getForm();

        $form->handleRequest($request);
        $connected = false;
        $tablesExist = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $drivers = [
                'mysql' => 'pdo_mysql',
                'postgres' => 'pdo_pgsql',
                'sqlite' => 'pdo_sqlite',
            ];
            $driver = $drivers[$form->get('system')->getData()] ?? '';

            $data = [
                'database_system' => $form->get('system')->getData(),
                'database_driver' => $driver,
                'database_host' => $form->get('host')->getData(),
                'database_port' => $form->get('port')->getData(),
                'database_name' => $form->get('database')->getData(),
                'database_user' => $form->get('user')->getData(),
                'database_password' => $form->get('password')->getData()
            ];

            try {
                $tmpConnection = DriverManager::getConnection([
                    'host' => $data['database_host'] ?? null,
                    'port' => $data['database_port'] ?? null,
                    'dbname' => $data['database_name'] ?? null,
                    'user' => $data['database_user'] ?? null,
                    'password' => $data['database_password'] ?? null,
                    'driver' => $data['database_driver'],
                ]);

                // Force the connection
                $data['database_version'] = $tmpConnection->getServerVersion();
                $connected = $tmpConnection->isConnected();

                /** @var AbstractSchemaManager $schemaManager */
                $schemaManager = $tmpConnection->createSchemaManager();
                $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
                foreach ($metadata as $classMetadata) {
                    if ($schemaManager->tablesExist([$classMetadata->getTableName()])) {
                        $tablesExist = true;
                        break;
                    }
                }

                if ($connected && !$tablesExist) {
                    $this->configHelper->writeEnvironmentConfig($data);
                }
            } catch (ConnectionException|Exception $e) {
                $connected = false;
            }

            // Save the database connection in the session and continue with the setup
            if ($connected && !$tablesExist) {
                return $this->redirectToRoute('setup_other');
            }
        }

        return $this->render('setup/database.html.twig', [
            'form' => $form->createView(),
            'submitted' => $form->isSubmitted(),
            'connected' => $connected,
            'tablesExist' => $tablesExist
        ]);
    }

    #[Route('/other', name: 'setup_other')]
    public function other(Request $request): Response
    {
        if ($this->setupHelper->isInstalled()) {
            return $this->redirectToRoute('dashboard');
        }

        $form = $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->add('name', TextType::class, [
                'label' => 'setup.other.form.name',
                'constraints' => [
                    new Regex([
                        'pattern' => '/:|%3A|%3a/',
                        'match' => false,
                        'message' => 'settings.mosparoName.colonNotAllowed',
                    ]),
                ],
            ])
            ->add('emailAddress', TextType::class, ['label' => 'setup.other.form.emailAddress'])
            ->add('password', PasswordFormType::class, [
                'label' => 'setup.other.form.password',
                'mapped' => false,
                'required' => true,
                'is_new_password' => false,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->configHelper->writeEnvironmentConfig([
                'mosparo_name' => $form->get('name')->getData(),
                'encryption_key' => $this->setupHelper->generateEncryptionKey(),
                'secret' => $this->setupHelper->generateEncryptionKey(),
            ]);

            $request->getSession()->set('setupUserEmailAddress', $form->get('emailAddress')->getData());
            $request->getSession()->set('setupUserPassword', $form->get('password')->get('plainPassword')->getData());

            return $this->redirectToRoute('setup_install');
        }

        return $this->render('setup/other.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/install', name: 'setup_install')]
    public function install(Request $request): Response
    {
        if ($this->setupHelper->isInstalled()) {
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

        return $this->redirectToRoute('setup_install_continuation');
    }

    #[Route('/install-continuation', name: 'setup_install_continuation')]
    public function installContinuation(Request $request): Response
    {
        $session = $request->getSession();

        // Create user
        try {
            $this->setupHelper->createUser($session->get('setupUserEmailAddress'), $session->get('setupUserPassword'));
        } catch (UserAlreadyExistsException|AdminUserAlreadyExistsException $e) {
            // Ignore this exception since the user exists, everything should be good.
        }

        $tokenGenerator = new TokenGenerator();

        $this->configHelper->writeEnvironmentConfig([
            'mosparo_installed' => true,
            'mosparo_installed_version' => Kernel::VERSION,
            'mosparo_assets_version' => $tokenGenerator->generateShortToken(),
        ]);

        // Clear the cache after the installation
        $input = new ArrayInput(array(
            'command' => 'cache:clear',
            '-n'
        ));

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();
        $application->run($input, $output);
        $output->fetch();

        return $this->render('setup/install.html.twig');
    }
}