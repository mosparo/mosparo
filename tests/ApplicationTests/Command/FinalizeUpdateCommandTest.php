<?php

namespace Mosparo\Tests\ApplicationTests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\Migration\OptimizeRuleItemsAfterUpdateHelper;
use Mosparo\Helper\Migration\V14OptimizeRulesHelper;
use Mosparo\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class FinalizeUpdateCommandTest extends KernelTestCase
{
    const ENTRYPOINTS = '{"entrypoints":{"mosparo-frontend":{"css":["/build/mosparo-frontend.test.css"]}}}';

    const BASE_CSS = '.mosparo__container { color: red; }';

    protected ?string $buildDirectory = null;

    protected ?string $configFilePath = null;

    protected ?string $configFileContent = null;

    protected ?int $ruleId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep the generated CSS in the shared cache so the test does not write into /public/resources/.
        $_ENV['MOSPARO_PREPARE_CSS_FILES_IN_SHARED_CACHE'] = '1';
        putenv('MOSPARO_PREPARE_CSS_FILES_IN_SHARED_CACHE=1');

        // The command stores the installed version in the configuration file, so keep the original content.
        $this->configFilePath = self::getContainer()->get(ConfigHelper::class)->getEnvironmentConfigFilePath();
        $this->configFileContent = file_get_contents($this->configFilePath);
    }

    protected function tearDown(): void
    {
        if ($this->ruleId !== null) {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            $entityManager
                ->createQuery(sprintf('DELETE FROM %s i WHERE i.rule = :rule', RuleItem::class))
                ->setParameter('rule', $this->ruleId)
                ->execute();
            $entityManager
                ->createQuery(sprintf('DELETE FROM %s r WHERE r.id = :id', Rule::class))
                ->setParameter('id', $this->ruleId)
                ->execute();

            $this->ruleId = null;
        }

        if ($this->configFileContent !== null) {
            (new Filesystem())->dumpFile($this->configFilePath, $this->configFileContent);

            $this->configFileContent = null;
        }

        if ($this->buildDirectory !== null) {
            (new Filesystem())->remove($this->buildDirectory);

            $this->buildDirectory = null;
        }

        unset($_ENV['MOSPARO_PREPARE_CSS_FILES_IN_SHARED_CACHE']);
        putenv('MOSPARO_PREPARE_CSS_FILES_IN_SHARED_CACHE');

        parent::tearDown();
    }

    protected function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);

        return new CommandTester($application->find('mosparo:finalize-update'));
    }

    /**
     * Builds the assets the frontend resources are generated from, if the assets were not built already.
     */
    protected function prepareAssets(): void
    {
        $buildDirectory = self::getContainer()->getParameter('kernel.project_dir') . '/public/build';
        if (file_exists($buildDirectory)) {
            return;
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($buildDirectory);
        $filesystem->dumpFile($buildDirectory . '/entrypoints.json', self::ENTRYPOINTS);
        $filesystem->dumpFile($buildDirectory . '/mosparo-frontend.test.css', self::BASE_CSS);

        $this->buildDirectory = $buildDirectory;
    }

    /**
     * The command executes the database migrations, which loads the migration classes of the configured
     * database platform. MysqlMigrationsTest loads the classes of the MySQL platform, which have the same
     * names, so this test runs in its own process.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFinalizeUpdate(): void
    {
        $commandTester = $this->createCommandTester();
        $this->prepareAssets();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $projectRepository = $entityManager->getRepository(Project::class);
        $project = $projectRepository->find(1);

        // A user agent rule with the item types of the version 1.4, which the finalization has to convert.
        $rule = (new Rule())
            ->setProject($project)
            ->setName('User agents')
            ->setType('user-agent')
        ;
        $entityManager->persist($rule);

        foreach (['text', 'regex'] as $type) {
            $ruleItem = (new RuleItem())
                ->setProject($project)
                ->setRule($rule)
                ->setType($type)
                ->setValue('spam')
            ;
            $entityManager->persist($ruleItem);
        }

        $entityManager->flush();

        $this->ruleId = $rule->getId();

        // The rule items are prepared when they are stored, so reset the preparation to have open tasks.
        $entityManager->createQuery(sprintf('UPDATE %s i SET i.preparationVersion = NULL', RuleItem::class))->execute();
        $entityManager->createQuery(sprintf('UPDATE %s i SET i.preparationVersion = NULL', RulePackageRuleItemCache::class))->execute();
        $entityManager->clear();

        $v14OptimizeRulesHelper = self::getContainer()->get(V14OptimizeRulesHelper::class);
        $optimizeRuleItemsAfterUpdateHelper = self::getContainer()->get(OptimizeRuleItemsAfterUpdateHelper::class);

        $this->assertEquals(2, $v14OptimizeRulesHelper->countOpenTasks());
        $this->assertGreaterThan(0, $optimizeRuleItemsAfterUpdateHelper->countOpenTasks());

        $returnCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString(sprintf('Finalize the update to the version %s', Kernel::VERSION), $commandTester->getDisplay());

        // The finalization processed all open tasks of both migration helpers.
        $this->assertEquals(0, $v14OptimizeRulesHelper->countOpenTasks());
        $this->assertEquals(0, $optimizeRuleItemsAfterUpdateHelper->countOpenTasks());

        $types = [];
        foreach ($entityManager->getRepository(RuleItem::class)->findBy(['rule' => $this->ruleId]) as $ruleItem) {
            $types[] = $ruleItem->getType();
        }
        sort($types);

        $this->assertEquals(['uaRegex', 'uaText'], $types);

        // The design config hash connects a project to its frontend resources and is stored while they are built.
        $this->assertNotEmpty($projectRepository->find(1)->getConfigValue('designConfigHash'));

        $config = require $this->configFilePath;

        $this->assertEquals(Kernel::VERSION, $config['mosparo_installed_version']);
        $this->assertFalse($config['mosparo_finalize_update']);
    }

    public function testFinalizeUpdateWithoutUpdate(): void
    {
        $commandTester = $this->createCommandTester();

        // The configuration of the test installation contains a version which is not the installed one.
        self::getContainer()->get(ConfigHelper::class)->writeEnvironmentConfig([
            'mosparo_installed_version' => Kernel::VERSION,
        ]);

        $returnCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString('was finalized already', $commandTester->getDisplay());
    }

    public function testFinalizeUpdateFailsWithoutAssets(): void
    {
        $commandTester = $this->createCommandTester();

        $buildDirectory = self::getContainer()->getParameter('kernel.project_dir') . '/public/build';
        if (file_exists($buildDirectory)) {
            $this->markTestSkipped('The assets of the frontend are built in this installation.');
        }

        $returnCode = $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('The assets of the frontend were not found', $commandTester->getDisplay());
    }
}
