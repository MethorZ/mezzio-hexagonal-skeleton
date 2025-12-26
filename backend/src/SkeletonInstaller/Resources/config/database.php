<?php

/**
 * SwiftDb Database Configuration
 *
 * High-performance MySQL database layer with Laravel-style query builder
 *
 * @see https://github.com/MethorZ/swift-db
 */

declare(strict_types=1);

return [
    'database' => [
        'connections' => [
            'default' => [
                'dsn' => 'mysql:host=database;dbname=app_db;charset=utf8mb4',
                'username' => 'app_user',
                'password' => 'app_password',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
            // Optional: Read replica for load distribution
            // 'replica' => [
            //     'dsn' => 'mysql:host=replica;dbname=app_db;charset=utf8mb4',
            //     'username' => 'readonly_user',
            //     'password' => 'readonly_password',
            //     'options' => [
            //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            //         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            //         PDO::ATTR_EMULATE_PREPARES => false,
            //     ],
            // ],
        ],
        // Default connection name
        'default' => 'default',

        // Optional: Master/slave routing
        // 'write_to' => 'default',
        // 'read_from' => 'replica',

        // Optional: Mapping cache (recommended for production)
        // 'cache_dir' => 'data/cache/database',
    ],
];
