<?php

declare(strict_types=1);

namespace HealthCheck\Application\Service;

use HealthCheck\Application\Request\HealthCheckRequest;
use HealthCheck\Application\Response\HealthCheckResponse;

/**
 * Health Check Service
 *
 * Simple application service for health check logic.
 * In hexagonal architecture, this is the application layer that contains business logic.
 */
final readonly class HealthCheckService
{
    /**
     * Execute health check
     */
    public function check(HealthCheckRequest $request): HealthCheckResponse
    {
        $details = [];

        if ($request->detailed) {
            $details = [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ];
        }

        return new HealthCheckResponse(
            status: 'ok',
            timestamp: (new \DateTimeImmutable())->format('c'),
            version: $this->getAppVersion(),
            details: $details,
        );
    }

    private function getAppVersion(): string
    {
        // You can load this from composer.json or a VERSION file
        return '1.0.0';
    }
}

