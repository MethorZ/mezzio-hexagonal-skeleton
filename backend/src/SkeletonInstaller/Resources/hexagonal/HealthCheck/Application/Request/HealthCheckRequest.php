<?php

declare(strict_types=1);

namespace HealthCheck\Application\Request;

/**
 * Health Check Request DTO
 *
 * Simple request object for health check endpoint.
 * Automatically populated from query parameters by methorz/http-dto.
 */
final readonly class HealthCheckRequest
{
    public function __construct(
        public bool $detailed = false,
    ) {}
}

