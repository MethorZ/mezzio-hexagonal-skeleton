<?php

/**
 * Global Configuration
 *
 * Settings that apply to ALL environments (dev, test, prod).
 * This file is always loaded via config.php pattern matching.
 *
 * For environment-specific settings, use:
 * - config/environment/{development,testing,production}.php (selected by APP_ENV, default production)
 * - local.php (developer-specific, not committed)
 */

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

return [
    'dependencies' => [
        'abstract_factories' => [
            // Automatic dependency wiring for all classes
            // No need to register in module ConfigProviders anymore
            ReflectionBasedAbstractFactory::class,
        ],
    ],
];
