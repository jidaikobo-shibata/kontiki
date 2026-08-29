<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Cli\Command;

use Jidaikobo\Kontiki\Cli\Installer;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'install', description: 'Install Kontiki into a Composer project')]
final class InstallCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('project-dir', null, InputOption::VALUE_REQUIRED, 'Composer project directory')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Site name')
            ->addOption('language', null, InputOption::VALUE_REQUIRED, 'Site language: en or ja')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'PHP timezone identifier')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'production or staging')
            ->addOption('admin-path', null, InputOption::VALUE_REQUIRED, 'Administration directory name')
            ->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Absolute public base URL')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show the planned files without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $options = $this->collectOptions($input, $output);
            $installer = new Installer($options['project_dir']);
            $plan = $installer->plan($options);

            $io->title('Kontiki installation plan');
            $io->definitionList(
                ['Project directory' => $options['project_dir']],
                ['Site name' => $options['name']],
                ['Environment' => $options['environment']],
                ['Administration path' => $options['admin_path']],
                ['Base URL' => $options['base_url']]
            );
            $io->section('Files to create');
            $io->listing($plan);

            if ((bool) $input->getOption('dry-run')) {
                $io->success('Dry run completed; no files were written.');
                return Command::SUCCESS;
            }

            if ($input->isInteractive()) {
                $confirmed = $this->questionHelper()->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion('Continue with installation? [y/N] ', false)
                );
                if (!$confirmed) {
                    $io->note('Installation cancelled.');
                    return Command::SUCCESS;
                }
            }

            $credentials = $installer->install($options, $output);
            $io->success('Kontiki installation completed.');
            $io->warning('Store these initial credentials securely. They are shown only once.');
            $io->definitionList(
                ['Username' => $credentials['username']],
                ['Password' => $credentials['password']]
            );

            return Command::SUCCESS;
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @return array{
     *     project_dir: string,
     *     name: string,
     *     language: string,
     *     timezone: string,
     *     environment: string,
     *     admin_path: string,
     *     base_url: string
     * }
     */
    private function collectOptions(InputInterface $input, OutputInterface $output): array
    {
        $projectDir = $this->value($input, 'project-dir', getcwd() ?: '.');
        $name = $this->valueOrAsk(
            $input,
            $output,
            'name',
            'Site name',
            $this->environmentDefault('KONTIKI_SITE_NAME', 'My CMS')
        );
        $language = $this->choiceOrAsk(
            $input,
            $output,
            'language',
            'Site language',
            ['en', 'ja'],
            $this->environmentDefault('KONTIKI_LANGUAGE', 'en') ?? 'en'
        );
        $timezone = $this->valueOrAsk(
            $input,
            $output,
            'timezone',
            'Timezone',
            $this->detectTimezone()
        );
        $environment = $this->choiceOrAsk(
            $input,
            $output,
            'environment',
            'Environment',
            ['production', 'staging'],
            'production'
        );
        $adminPath = $this->valueOrAsk($input, $output, 'admin-path', 'Administration path', 'admin');
        $baseUrl = $this->valueOrAsk(
            $input,
            $output,
            'base-url',
            'Base URL',
            $this->environmentDefault('KONTIKI_BASE_URL')
        );

        $realProjectDir = realpath($projectDir);
        if ($realProjectDir === false || !is_dir($realProjectDir)) {
            throw new RuntimeException("Project directory does not exist: {$projectDir}");
        }
        if (!is_file($realProjectDir . '/composer.json')) {
            throw new RuntimeException('The project directory must contain composer.json.');
        }
        if (!in_array($language, ['en', 'ja'], true)) {
            throw new RuntimeException('Language must be en or ja.');
        }
        if (!in_array($environment, ['production', 'staging'], true)) {
            throw new RuntimeException('Environment must be production or staging.');
        }
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            throw new RuntimeException('Enter a valid PHP timezone identifier.');
        }
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]{0,63}\z/D', $adminPath) !== 1) {
            throw new RuntimeException('Administration path must use 1-64 letters, numbers, underscores, or hyphens.');
        }
        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Base URL must be an absolute URL.');
        }
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Base URL must use http or https.');
        }

        return [
            'project_dir' => $realProjectDir,
            'name' => $name,
            'language' => $language,
            'timezone' => $timezone,
            'environment' => $environment,
            'admin_path' => $adminPath,
            'base_url' => rtrim($baseUrl, '/'),
        ];
    }

    private function value(InputInterface $input, string $option, string $default): string
    {
        $value = $input->getOption($option);
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function valueOrAsk(
        InputInterface $input,
        OutputInterface $output,
        string $option,
        string $label,
        ?string $default
    ): string {
        $value = $input->getOption($option);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        if (!$input->isInteractive()) {
            if ($default !== null) {
                return $default;
            }
            throw new RuntimeException("--{$option} is required with --no-interaction.");
        }

        $prompt = $default === null
            ? "{$label}: "
            : "{$label} [{$default}]: ";
        $answer = $this->questionHelper()->ask(
            $input,
            $output,
            new Question($prompt, $default)
        );
        if (!is_string($answer) || trim($answer) === '') {
            throw new RuntimeException("{$label} is required.");
        }

        return trim($answer);
    }

    /** @param list<string> $choices */
    private function choiceOrAsk(
        InputInterface $input,
        OutputInterface $output,
        string $option,
        string $label,
        array $choices,
        string $default
    ): string {
        $value = $input->getOption($option);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        if (!$input->isInteractive()) {
            return $default;
        }

        if (!in_array($default, $choices, true)) {
            $default = $choices[0];
        }
        $question = new ChoiceQuestion(
            "{$label} [{$default}]",
            $choices,
            array_search($default, $choices, true)
        );
        $answer = $this->questionHelper()->ask($input, $output, $question);

        return is_string($answer) ? $answer : $default;
    }

    private function questionHelper(): QuestionHelper
    {
        $helper = $this->getHelper('question');
        if (!$helper instanceof QuestionHelper) {
            throw new RuntimeException('Symfony Console question helper is unavailable.');
        }

        return $helper;
    }

    private function environmentDefault(string $name, ?string $fallback = null): ?string
    {
        $value = getenv($name);
        return is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
    }

    private function detectTimezone(): string
    {
        $candidates = [
            $this->environmentDefault('TZ'),
            ini_get('date.timezone'),
            $this->readOsTimezone(),
            date_default_timezone_get(),
            'UTC',
        ];

        foreach ($candidates as $candidate) {
            if (
                is_string($candidate)
                && in_array(trim($candidate), timezone_identifiers_list(), true)
            ) {
                return trim($candidate);
            }
        }

        return 'UTC';
    }

    private function readOsTimezone(): ?string
    {
        $path = '/etc/timezone';
        if (!is_readable($path)) {
            return null;
        }

        $timezone = file_get_contents($path);
        return is_string($timezone) && trim($timezone) !== ''
            ? trim($timezone)
            : null;
    }
}
