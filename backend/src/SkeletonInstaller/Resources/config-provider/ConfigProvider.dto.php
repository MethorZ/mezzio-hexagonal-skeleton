<?php

declare(strict_types=1);

namespace App\Application\Config;

use App\Application\Handler\HomeHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Application Configuration Provider
 *
 * Configured for methorz/http-dto usage with automatic DtoHandler wrapping.
 * All dependencies (ValidatorInterface, dto.error_handler) are provided by
 * the http-dto ConfigProvider - this is truly zero-config!
 */
class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'routes'       => $this->getRoutes(),
        ];
    }

    /**
     * @return array<string, array<string, callable>>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                LoggerInterface::class => static function (ContainerInterface $container): LoggerInterface {
                    /** @var array<string, mixed> $config */
                    $config = $container->has('config') ? $container->get('config') : [];
                    $loggingConfig = $config['logging'] ?? [];

                    $name = $loggingConfig['name'] ?? 'app';
                    $logPath = $loggingConfig['path'] ?? 'data/logs/application.log';
                    $logLevel = $loggingConfig['level'] ?? 'INFO';

                    // Make log path absolute (relative to application root)
                    if (!str_starts_with($logPath, '/')) {
                        $logPath = getcwd() . '/' . $logPath;
                    }

                    // Ensure log directory exists
                    $logDir = dirname($logPath);
                    if (!is_dir($logDir)) {
                        mkdir($logDir, 0775, true);
                    }

                    // Convert string level to Monolog Level enum
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
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return [
            [
                'name'            => 'home',
                'path'            => '/',
                // DtoHandlerInterface handlers are automatically wrapped by DtoHandlerAbstractFactory
                'middleware'      => HomeHandler::class,
                'allowed_methods' => ['GET'],
            ],
            [
                'name'            => 'health',
                'path'            => '/health',
                // DtoHandlerInterface handlers are automatically wrapped by DtoHandlerAbstractFactory
                'middleware'      => HomeHandler::class,
                'allowed_methods' => ['GET'],
            ],
        ];
    }
}

