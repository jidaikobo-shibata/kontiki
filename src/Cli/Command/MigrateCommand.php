<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Cli\Command;

use Jidaikobo\Kontiki\Cli\MigrationManager;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'migrate', description: 'Apply pending Kontiki database migrations')]
final class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('project-dir', null, InputOption::VALUE_REQUIRED, 'Composer project directory')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'Site environment', 'production');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $manager = new MigrationManager($this->option($input, 'project-dir', getcwd() ?: '.'));
            $environment = $this->option($input, 'environment', 'production');
            $details = $manager->describe($environment);

            $io->title('Kontiki database migration');
            $io->definitionList(
                ['Project directory' => $details['project_dir']],
                ['Environment' => $details['environment']],
                ['Database' => $details['database']]
            );

            if ($input->isInteractive() && !$this->confirmed($input, $output)) {
                $io->note('Migration cancelled.');
                return Command::SUCCESS;
            }

            $manager->migrate($environment, $output);
            $io->success('Database migrations completed.');
            return Command::SUCCESS;
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function option(InputInterface $input, string $name, string $default): string
    {
        $value = $input->getOption($name);
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function confirmed(InputInterface $input, OutputInterface $output): bool
    {
        $helper = $this->getHelper('question');
        if (!$helper instanceof QuestionHelper) {
            throw new RuntimeException('Symfony Console question helper is unavailable.');
        }

        return (bool) $helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Apply pending migrations? [y/N] ', false)
        );
    }
}
