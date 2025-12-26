<?php

declare(strict_types=1);

namespace App\Application\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Example Console Command - Health Check
 *
 * Demonstrates Symfony Console integration.
 * Run: php bin/console app:health-check
 */
#[AsCommand(
    name: 'app:health-check',
    description: 'Perform application health check',
)]
final class HealthCheckCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'detailed',
                'd',
                InputOption::VALUE_NONE,
                'Show detailed health information'
            )
            ->setHelp(
                <<<'HELP'
                The <info>app:health-check</info> command performs a health check of the application.

                <info>php bin/console app:health-check</info>

                For detailed information:
                <info>php bin/console app:health-check --detailed</info>
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Application Health Check');

        // Basic checks
        $checks = [
            'PHP Version' => PHP_VERSION,
            'Environment' => $_ENV['APP_ENV'] ?? 'production',
        ];

        $io->table(
            ['Check', 'Status'],
            array_map(fn($key, $value) => [$key, $value], array_keys($checks), $checks)
        );

        // Detailed checks if requested
        if ($input->getOption('detailed')) {
            $io->section('Detailed Information');

            $detailed = [
                'Memory Limit' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time') . 's',
                'Loaded Extensions' => count(get_loaded_extensions()),
            ];

            $io->table(
                ['Parameter', 'Value'],
                array_map(fn($key, $value) => [$key, $value], array_keys($detailed), $detailed)
            );
        }

        $io->success('Application is healthy!');

        return Command::SUCCESS;
    }
}

