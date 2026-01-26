<?php

/**
 * CORS Configuration
 *
 * Cross-Origin Resource Sharing (CORS) settings for API endpoints.
 * Configure allowed origins, methods, headers, and credentials.
 *
 * Override in cors.local.php for environment-specific settings.
 */

declare(strict_types=1);

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Cors\Configuration\ConfigurationInterface;

return [
    ConfigurationInterface::CONFIGURATION_IDENTIFIER => [
        // Allowed origins - configure via CORS_ALLOWED_ORIGINS env var
        // Examples:
        //   - Single origin: 'https://example.com'
        //   - Multiple origins: 'https://example.com,https://app.example.com'
        //   - All origins (dev only): '*'
        'allowed_origins' => array_filter(
            explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
            fn($origin) => $origin !== '',
        ),

        // Allowed HTTP methods
        'allowed_methods' => [
            RequestMethodInterface::METHOD_GET,
            RequestMethodInterface::METHOD_POST,
            RequestMethodInterface::METHOD_PUT,
            RequestMethodInterface::METHOD_PATCH,
            RequestMethodInterface::METHOD_DELETE,
            RequestMethodInterface::METHOD_OPTIONS,
        ],

        // Allowed request headers
        'allowed_headers' => [
            'Accept',
            'Authorization',
            'Content-Type',
            'X-Requested-With',
        ],

        // Max age for preflight cache (in seconds) - 1 hour
        'allowed_max_age' => '3600',

        // Allow credentials (cookies, auth headers)
        'credentials_allowed' => true,

        // Expose headers to the client
        'exposed_headers' => [
            'X-Total-Count',
            'X-Page-Count',
        ],
    ],
];

