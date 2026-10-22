<?php

declare(strict_types=1);

namespace App\Application\Config;

use App\Application\Error\JsonErrorResponseGeneratorFactory;
use App\Application\Handler\HomeHandler;
use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Middleware\ErrorResponseGenerator;

/**
 * Application Configuration Provider
 *
 * Minimal configuration for the skeleton application.
 * Add your own handlers, factories, and routes here.
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
     * @return array<string, array<string, callable|string>>
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                HomeHandler::class => HomeHandler::class,
            ],
            'factories'  => [
                // Renders exceptions caught by Mezzio's ErrorHandler as JSON
                ErrorResponseGenerator::class => JsonErrorResponseGeneratorFactory::class,
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
                'middleware'      => [HomeHandler::class],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name'            => 'health',
                'path'            => '/health',
                'middleware'      => [HomeHandler::class],
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
        ];
    }
}
