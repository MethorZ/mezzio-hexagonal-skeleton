<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

/**
 * Global Configuration
 *
 * Settings that apply to ALL environments (dev, test, prod).
 * This file is always loaded via config.php pattern matching.
 *
 * For environment-specific settings, use:
 * - app.development.php (dev only)
 * - app.production.php (prod only)
 * - local.php (developer-specific, not committed)
 */

return [
    'dependencies' => [
        'abstract_factories' => [
            // Automatic dependency wiring for all classes
            // No need to register in module ConfigProviders anymore
            ReflectionBasedAbstractFactory::class,
        ],
    ],
];

