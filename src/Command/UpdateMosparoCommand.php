<?php

namespace Mosparo\Command;

use Mosparo\Helper\SetupHelper;
use Mosparo\Helper\UpdateHelper;
use Mosparo\Kernel;
use Mosparo\Message\UpdateMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'mosparo:self-update')]
class UpdateMosparoCommand extends Command
{
    protected UpdateHelper $updateHelper;

    protected SetupHelper $setupHelper;

    protected string $projectDirectory;

    protected $lastSection;

    protected ?UpdateMessage $lastMessage = null;

    public function __construct(UpdateHelper $updateHelper, SetupHelper $setupHelper, string $projectDirectory)
    {
        parent::__construct();

        $this->updateHelper = $updateHelper;
        $this->setupHelper = $setupHelper;
        $this->projectDirectory = $projectDirectory;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Updates mosparo to the newest available version.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $user = false;
        $userOfDotEnv = false;
        if (function_exists('posix_getpwuid')) {
            $user = $_SERVER['USER'] ?? false;
            $userOfDotEnvData = posix_getpwuid(fileowner($this->projectDirectory . '/.env'));
            $userOfDotEnv = $userOfDotEnvData['name'] ?? false;
        }

        // If this command is executed as root user or as user who do not own the files, the user has to confirm before continuing
        if ($user !== false && ($user === 'root' || ($userOfDotEnv !== false && $user !== $userOfDotEnv))) {
            $output->writeln('');
            $output->writeln('<fg=black;bg=red> Your user is not the owner of these files or is the root user. You should execute the command as owner of the files. </>');
            $output->writeln('');
            $question = new ConfirmationQuestion('Do you want to continue with the update? (n) ', false);

            // Abort if the user didn't want to continue
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        } else if ($user === false) {
            // Backup in case ext-posix is not available
            $output->writeln('');
            $output->writeln('<fg=black;bg=red> This command cannot determine, if you\'re using the correct user to update mosparo. </>');
            $output->writeln('<fg=black;bg=red> Please execute the command with the owner of the mosparo files. </>');
            $output->writeln('');
            $question = new ConfirmationQuestion('Do you want to continue with the update? (n) ', false);

            // Abort if the user didn't want to continue
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $this->updateHelper->getCachedUpdateData(true);
        if (!$this->updateHelper->isUpdateAvailable() && !$this->updateHelper->isUpgradeAvailable()) {
            $output->writeln('No updates available.');

            return Command::SUCCESS;
        }

        if ($this->updateHelper->isUpdateAvailable() && $this->updateHelper->isUpgradeAvailable()) {
            $output->writeln('');
            $output->writeln('<fg=yellow>#</>');
            $output->writeln('<fg=yellow># A new major version is available.</>');
            $output->writeln('<fg=yellow># Please update to the latest version of your major version befor you can upgrade.</>');
            $output->writeln('<fg=yellow>#</>');
            $output->writeln('');
        }

        $result = Command::SUCCESS;
        if ($this->updateHelper->isUpdateAvailable()) {
            // Proceed with the update

            $output->writeln('<question> Update for mosparo available! </>');

            $versionData = $this->updateHelper->getAvailableUpdateData();
            $result = $this->updateMosparo($input, $output, $versionData);
        } else if ($this->updateHelper->isUpgradeAvailable()) {
            // Proceed with the upgrade

            $output->writeln('<question> Upgrade for mosparo available! </>');

            $upgradeData = $this->updateHelper->getAvailableUpgradeData();
            $result = $this->upgradeMosparo($input, $output, $upgradeData);
        }

        return $result;
    }

    protected function updateMosparo(InputInterface $input, OutputInterface $output, array $versionData, $isUpgrade = false)
    {
        $helper = $this->getHelper('question');

        $output->writeln('');
        $output->writeln(sprintf("<fg=red>Installed version:</>\t<comment>%s</>", Kernel::VERSION));
        $output->writeln(sprintf("<fg=green>New version:</>\t\t<comment>%s</>", $versionData['number']));
        $output->writeln('');

        $question = new ConfirmationQuestion(sprintf('Do you want to %s mosparo to version %s? (n) ', ($isUpgrade) ? 'upgrade' : 'update', $versionData['number']), false);

        // Abort if the user didn't want to continue
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $output->writeln('');
        $output->writeln('<fg=red>This command does not backup your mosparo installation and your database.</>');
        $output->writeln('');
        $question = new ConfirmationQuestion(sprintf('Do you want to continue with the %s? (n) ', ($isUpgrade) ? 'upgrade' : 'update'), false);

        // Abort if the user didn't want to continue
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln(sprintf('%s stopped', ($isUpgrade) ? 'Upgrade' : 'Update'));

            return Command::SUCCESS;
        }

        $output->writeln('');

        // Register the output handler
        $that = $this;
        $this->updateHelper->setOutputHandler(function (UpdateMessage $message) use ($that, $output) {
            if ($that->lastMessage === null || $message->getContext() !== $that->lastMessage->getContext()) {
                if ($that->lastMessage !== null) {
                    $section = $that->lastSection;

                    if ($message->isError()) {
                        $section->overwrite(sprintf('<fg=red>❌ %s</>', $this->lastMessage->getMessage()));
                    } else {
                        $section->overwrite(sprintf('<fg=green>✅ %s</>', $this->lastMessage->getMessage()));
                    }
                }

                $section = $output->section();
                $section->writeln('');

                $that->lastSection = $section;
                $that->lastMessage = $message;
            } else {
                $section = $that->lastSection;
            }

            if ($message->isError()) {
                $section->overwrite(sprintf('<fg=red>❌ %s</>', $message->getMessage()));
            } else {
                if ($message->isInProgress()) {
                    $section->overwrite(sprintf('<fg=white>►  %s</>', $message->getMessage()));
                } else {
                    $section->overwrite(sprintf('<fg=green>✅ %s</>', $message->getMessage()));
                }
            }
        });

        // Start the update
        $this->updateHelper->updateMosparo($versionData);

        // Cleanup the content
        $this->updateHelper->output(new UpdateMessage('global', UpdateMessage::STATUS_COMPLETED, sprintf('%s completed', ($isUpgrade) ? 'Upgrade' : 'Update')));

        $output->writeln('');
        $output->writeln(sprintf('<bg=green;fg=black> %s finished! </>', ($isUpgrade) ? 'Upgrade' : 'Update'));
        $output->writeln('');
        $output->writeln(sprintf('Please execute the migrations to finalize the %s:', ($isUpgrade) ? 'upgrade' : 'update'));
        $output->writeln('');
        $output->writeln('  php bin/console doctrine:migrations:migrate');
        $output->writeln('  php bin/console cache:clear');
        $output->writeln('');

        return Command::SUCCESS;
    }

    protected function upgradeMosparo(InputInterface $input, OutputInterface $output, array $upgradeData)
    {
        $helper = $this->getHelper('question');

        $majorVersionData = $upgradeData['majorVersionData'];
        $versionData = $upgradeData['versionData'];

        $output->writeln('');
        $output->writeln(sprintf("<fg=red>Installed major version:</>\t<comment>%s</>", Kernel::MAJOR_VERSION));
        $output->writeln(sprintf("<fg=green>New major version:</>\t\t<comment>%s</>", $majorVersionData['number']));
        $output->writeln('');

        $question = new ConfirmationQuestion(sprintf('Do you want to upgrade mosparo to the major version %s? (n) ', $majorVersionData['number']), false);

        // Abort if the user didn't want to continue
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        // Check the requirements
        [ $meetPrerequisites, $prerequisites ] = $this->setupHelper->checkUpgradePrerequisites($majorVersionData ?? []);

        if (!$meetPrerequisites) {
            $output->writeln('');
            $output->writeln('<fg=red>❌ Your web hosting does not meet all requirements.</>');
            $output->writeln('');

            foreach ($prerequisites as $type => $subPrerequisites) {
                $name = $this->getTypeName($type);
                $output->writeln($name);
                $output->writeln(str_repeat('-', strlen($name)));

                foreach ($subPrerequisites as $subType => $data) {
                    $name = $this->getTypeName($type, $subType);
                    $color = 'green';
                    $icon = '✅ ';
                    if ($data['required'] && !$data['pass']) {
                        $color = 'red';
                        $icon = '❌ ';
                    }

                    $output->writeln(sprintf('<fg=%s>%s%s</>', $color, $icon, $name));

                    if ($type === 'general' && $subType === 'minPhpVersion' && $data['required'] && !$data['pass']) {
                        $output->writeln('');
                        $output->writeln(sprintf("<fg=red>Available:</>\t<fg=red>  %s</>", $data['available']));
                        $output->writeln(sprintf("<fg=green>Required:</>\t<fg=green>≥ %s</>", $data['required']));
                    }
                }

                $output->writeln('');
            }

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<fg=green>✅ Your web hosting meets all requirements for this new major version.</>');
        $output->writeln('');
        $question = new ConfirmationQuestion('Do you want to continue with the upgrade? (n) ', false);

        // Abort if the user didn't want to continue
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Upgrade aborted');

            return Command::SUCCESS;
        }

        return $this->updateMosparo($input, $output, $versionData, true);
    }

    protected function getTypeName($type, $subType = null): string
    {
        if ($type === 'general') {
            if ($subType === 'minPhpVersion') {
                return 'Minimum PHP version';
            }

            return 'General';
        } else if ($type === 'phpExtension') {
            if ($subType !== null) {
                return $subType;
            }

            return 'PHP Extension';
        } else if ($type === 'writeAccess') {
            if ($subType !== null) {
                return $subType;
            }

            return 'Write access';
        }

        return $type;
    }
}