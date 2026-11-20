<?php

namespace Mosparo\Tests\ApplicationTests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;

class RefreshFrontendResourcesCommandTest extends KernelTestCase
{
    const ENTRYPOINTS = '{"entrypoints":{"mosparo-frontend":{"css":["/build/mosparo-frontend.test.css"]}}}';

    const BASE_CSS = '.mosparo__container { color: red; }';

    protected ?string $buildDirectory = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep the generated CSS in the shared cache so the test does not write into /public/resources/.
        $_ENV['MOSPARO_PREPARE_CSS_FILES_IN_SHARED_CACHE'] = '1';
        putenv('MOSPARO_PREPARE_CSS_FILES_IN_SHARED_CACHE=1');
    }

    protected function tearDown(): void
    {
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

        return new CommandTester($application->find('mosparo:refresh-frontend-resources'));
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

    public function testRefreshFrontendResources(): void
    {
        $commandTester = $this->createCommandTester();
        $this->prepareAssets();

        $projectRepository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Project::class);
        $numberOfProjects = count($projectRepository->findAll());

        $returnCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $returnCode);
        $this->assertStringContainsString(sprintf('Refreshed the frontend resources of %d project(s).', $numberOfProjects), $commandTester->getDisplay());

        // The design config hash connects a project to its frontend resources and is stored while they are built.
        $project = $projectRepository->find(1);

        $this->assertNotEmpty($project->getConfigValue('designConfigHash'));
    }

    public function testRefreshFrontendResourcesFailsWithoutAssets(): void
    {
        $commandTester = $this->createCommandTester();

        // Replace the Encore lookup of the default build with a missing entrypoints file, so the test does not
        // depend on whether the assets are built in this installation.
        self::getContainer()->set('webpack_encore.entrypoint_lookup[_default]', new EntrypointLookup(__DIR__ . '/../Fixtures/does-not-exist/entrypoints.json'));

        $returnCode = $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $returnCode);
        $this->assertStringContainsString('The assets of the frontend were not found', $commandTester->getDisplay());
    }
}
