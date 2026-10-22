<?php

declare(strict_types=1);

namespace Core\Infrastructure\Config;

use Core\Infrastructure\Error\JsonErrorResponseGeneratorFactory;
use Mezzio\Middleware\ErrorResponseGenerator;

/**
 * Core ConfigProvider
 *
 * Provides shared infrastructure used by every module.
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
        ];
    }

    /**
     * Get dependency injection configuration
     *
     * @return array<string, array<string, string>>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                // Renders exceptions caught by Mezzio's ErrorHandler as JSON
                ErrorResponseGenerator::class => JsonErrorResponseGeneratorFactory::class,
            ],
        ];
    }
}
