<?php

declare(strict_types=1);

/**
 * Doctrine Migrations Configuration
 *
 * This file is automatically loaded by vendor/bin/doctrine-migrations
 *
 * Common commands:
 *   vendor/bin/doctrine-migrations list              - Show all migrations
 *   vendor/bin/doctrine-migrations status             - Show migration status
 *   vendor/bin/doctrine-migrations migrate            - Execute migrations
 *   vendor/bin/doctrine-migrations generate           - Generate new migration
 *   vendor/bin/doctrine-migrations version --add xxx  - Mark migration as executed
 *   vendor/bin/doctrine-migrations version --delete xxx - Mark migration as not executed
 */

// Return configuration array for Doctrine Migrations
return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 191,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],
    'migrations_paths' => [
        'Database\\Migrations' => __DIR__ . '/migrations',
    ],
    'all_or_nothing' => true,
    'transactional' => true,
    'check_database_platform' => true,
    'organize_migrations' => 'none',
];
