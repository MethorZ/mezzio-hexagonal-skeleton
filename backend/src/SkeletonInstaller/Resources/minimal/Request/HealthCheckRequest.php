<?php

declare(strict_types=1);

namespace App\Application\Request;

/**
 * Health Check Request DTO
 *
 * Simple request object for health check endpoint.
 * Populated from query parameters (automatically when methorz/http-dto is installed).
 */
final readonly class HealthCheckRequest
{
    public function __construct(
        public bool $detailed = false,
    ) {
    }
}
