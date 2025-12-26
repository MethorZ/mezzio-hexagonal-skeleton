<?php

declare(strict_types=1);

/**
 * Doctrine Migrations Database Connection Configuration
 *
 * This file provides the database connection for Doctrine Migrations.
 * It's automatically loaded by the migrations CLI.
 */

use Doctrine\DBAL\DriverManager;

// Load application config to get database connection details
$config = require __DIR__ . '/config/config.php';
$dbConfig = $config['database'] ?? [];

// Extract connection parameters from SwiftDb config
$defaultConnectionName = $dbConfig['default'] ?? 'default';
$defaultConnection = $dbConfig['connections'][$defaultConnectionName] ?? [];
$dsn = $defaultConnection['dsn'] ?? '';
$username = $defaultConnection['username'] ?? '';
$password = $defaultConnection['password'] ?? '';

// Parse DSN to extract components for DBAL
// Format: mysql:host=database;dbname=app_db;charset=utf8mb4
preg_match('/mysql:host=([^;]+);dbname=([^;]+)/', $dsn, $matches);
$host = $matches[1] ?? 'database';
$dbname = $matches[2] ?? 'app_db';

// Create and return DBAL connection
$connectionParams = [
    'driver' => 'pdo_mysql',
    'host' => $host,
    'dbname' => $dbname,
    'user' => $username,
    'password' => $password,
    'charset' => 'utf8mb4',
];

return DriverManager::getConnection($connectionParams);

