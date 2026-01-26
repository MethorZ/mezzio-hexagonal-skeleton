<?php

/**
 * Doctrine Migrations Configuration
 *
 * Database schema versioning and migration management
 *
 * Zero-config approach:
 * - Uses environment variables with sensible defaults
 * - Works immediately with DATABASE_* environment variables
 * - Stores migrations in migrations/ directory
 *
 * @see https://www.doctrine-project.org/projects/migrations.html
 */

declare(strict_types=1);

return [
    'doctrine' => [
        'migrations' => [
            // Migrations namespace - where migration classes live
            'migrations_namespace' => $_ENV['MIGRATIONS_NAMESPACE'] ?? 'Database\\Migrations',

            // Migrations directory - where migration files are stored
            'migrations_directory' => $_ENV['MIGRATIONS_DIRECTORY'] ?? 'migrations',

            // Table name for tracking executed migrations
            'table_name' => $_ENV['MIGRATIONS_TABLE'] ?? 'doctrine_migration_versions',

            // Database connection configuration
            'connection' => [
                'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
                'host' => $_ENV['DB_HOST'] ?? 'database',
                'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                'dbname' => $_ENV['DB_NAME'] ?? 'app_db',
                'user' => $_ENV['DB_USER'] ?? 'app_user',
                'password' => $_ENV['DB_PASSWORD'] ?? 'app_password',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            ],

            // Optional: Organize migrations by version
            'organize_migrations' => $_ENV['MIGRATIONS_ORGANIZE_BY_YEAR'] ?? false,

            // Optional: Custom migration template
            // 'custom_template' => 'path/to/custom/template.php',

            // Optional: All or nothing transaction mode (default: true)
            'all_or_nothing' => filter_var(
                $_ENV['MIGRATIONS_ALL_OR_NOTHING'] ?? 'true',
                FILTER_VALIDATE_BOOLEAN,
            ),

            // Optional: Check database platform (recommended for production)
            'check_database_platform' => filter_var(
                $_ENV['MIGRATIONS_CHECK_PLATFORM'] ?? 'true',
                FILTER_VALIDATE_BOOLEAN,
            ),
        ],
    ],
];

