<?php

/**
 * Logging Configuration
 *
 * Configure structured logging with Monolog.
 * Override in logging.local.php for environment-specific settings.
 */

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    'logging' => [
        // Application name (used as logger channel name)
        'name' => 'app',

        // Log level: DEBUG, INFO, WARNING, ERROR, CRITICAL
        'level' => $_ENV['LOG_LEVEL'] ?? 'INFO',

        // Log file path (relative to backend root)
        'path' => $_ENV['LOG_PATH'] ?? 'data/logs/application.log',
    ],

    'dependencies' => [
        'factories' => [
            LoggerInterface::class => static function (ContainerInterface $container): LoggerInterface {
                /** @var array<string, mixed> $config */
                $config = $container->has('config') ? $container->get('config') : [];
                $loggingConfig = $config['logging'] ?? [];

                $name = $loggingConfig['name'] ?? 'app';
                $logPath = $loggingConfig['path'] ?? 'data/logs/application.log';
                $logLevel = $loggingConfig['level'] ?? 'INFO';

                // Resolve relative paths
                if (!str_starts_with($logPath, '/')) {
                    $logPath = getcwd() . '/' . $logPath;
                }

                // Map log level string to Monolog Level
                $level = match (strtoupper($logLevel)) {
                    'DEBUG' => Level::Debug,
                    'INFO' => Level::Info,
                    'NOTICE' => Level::Notice,
                    'WARNING' => Level::Warning,
                    'ERROR' => Level::Error,
                    'CRITICAL' => Level::Critical,
                    'ALERT' => Level::Alert,
                    'EMERGENCY' => Level::Emergency,
                    default => Level::Info,
                };

                $logger = new Logger($name);
                $logger->pushHandler(new StreamHandler($logPath, $level));

                return $logger;
            },
        ],
    ],
];
