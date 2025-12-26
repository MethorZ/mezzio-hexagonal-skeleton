<?php

/**
 * Cache Configuration
 *
 * Configure Symfony Cache component.
 */

declare(strict_types=1);

return [
    'cache' => [
        // Default cache adapter: filesystem, apcu, redis
        'adapter' => $_ENV['CACHE_ADAPTER'] ?? 'filesystem',

        // Cache directory (for filesystem adapter)
        'directory' => 'data/cache/app',

        // Default TTL (Time To Live) in seconds
        'default_ttl' => 300, // 5 minutes

        // Namespace for cache keys (prevents collisions)
        'namespace' => 'app',

        // Redis configuration (when using redis adapter)
        'redis' => [
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => (int) ($_ENV['REDIS_PORT'] ?? '6379'),
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        ],
    ],
];
