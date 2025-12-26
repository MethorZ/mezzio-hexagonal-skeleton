<?php

declare(strict_types=1);

namespace App\Application\Config;

use App\Application\Handler\HealthCheckHandler;
use Fig\Http\Message\RequestMethodInterface;

/**
 * Application ConfigProvider
 *
 * Provides dependency injection configuration and routes for the application.
 * ReflectionBasedAbstractFactory is configured globally in config/autoload/global.php.
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
     * Get dependency injection configuration
     *
     * @return array<string, array<string, string|callable|array<string>>>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                // Add explicit factories here if needed
                // ReflectionBasedAbstractFactory is now in global.php
            ],
        ];
    }

    /**
     * Get route configuration
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return [
            [
                'name'            => 'health',
                'path'            => '/health',
                'middleware'      => HealthCheckHandler::class,
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name'            => 'home',
                'path'            => '/',
                'middleware'      => HealthCheckHandler::class,
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
        ];
    }
}

