<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Cli\Command;

use Jidaikobo\Kontiki\Cli\MigrationManager;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'status', description: 'Show Kontiki configuration and migration status')]
final class StatusCommand extends Command
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

            $io->title('Kontiki status');
            $io->definitionList(
                ['Project directory' => $details['project_dir']],
                ['Environment' => $details['environment']],
                ['Database' => $details['database']]
            );

            $status = $manager->status($environment, $output);
            if ($status['hasMissingMigration']) {
                $io->error('The database records migrations that are missing from this package.');
                return Command::FAILURE;
            }
            if ($status['hasDownMigration']) {
                $io->warning('Pending database migrations were found.');
                return Command::INVALID;
            }

            $io->success('All Kontiki migrations are applied.');
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
}
