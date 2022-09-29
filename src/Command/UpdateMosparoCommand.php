<?php

namespace Mosparo\Command;

use Mosparo\Helper\UpdateHelper;
use Mosparo\Message\UpdateMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateMosparoCommand extends Command
{
    protected static $defaultName = 'mosparo:self-update';

    protected UpdateHelper $updateHelper;

    protected string $projectDirectory;

    protected $lastSection;

    protected ?UpdateMessage $lastMessage = null;

    public function __construct(UpdateHelper $updateHelper, string $projectDirectory)
    {
        parent::__construct();

        $this->updateHelper = $updateHelper;
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

        $this->updateHelper->checkForUpdates();
        if (!$this->updateHelper->isUpdateAvailable()) {
            $output->writeln('No updates available.');

            return Command::SUCCESS;
        }

        $versionData = $this->updateHelper->getAvailableUpdateData();

        $output->writeln('<question> Update for mosparo available! </>');
        $output->writeln('');
        $output->writeln(sprintf("<fg=red>Installed version:</>\t<comment>%s</>", $this->updateHelper->getMosparoVersion()));
        $output->writeln(sprintf("<fg=green>New version:</>\t\t<comment>%s</>", $versionData['version']));
        $output->writeln('');

        $question = new ConfirmationQuestion(sprintf('Do you want to update mosparo to version %s? (n) ', $versionData['version']), false);

        // Abort if the user didn't want to continue
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $output->writeln('');
        $output->writeln('<fg=red>This command does not backup your mosparo installation and your database.</>');
        $output->writeln('');
        $question = new ConfirmationQuestion('Do you want to continue with the update? (n) ', false);

        // Abort if the user didn't want to continue
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Update stopped');

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
        $this->updateHelper->output(new UpdateMessage('global', UpdateMessage::STATUS_COMPLETED, 'Update completed'));

        $output->writeln('');
        $output->writeln('<bg=green;fg=black> Update finalized! </>');
        $output->writeln('');
        $output->writeln('Please execute the migrations to finalize the update:');
        $output->writeln('');
        $output->writeln('  php bin/console doctrine:migrations:migrate');
        $output->writeln('  php bin/console cache:clear');
        $output->writeln('');

        return 0;
    }
}