<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Request\HealthCheckRequest;
use App\Application\Response\HealthCheckResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Health Check Handler
 *
 * Simple health check endpoint as a plain PSR-15 request handler.
 * Install methorz/http-dto to map requests to DTOs and serialize responses automatically.
 */
final readonly class HealthCheckHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dto = new HealthCheckRequest(
            detailed: filter_var(
                $request->getQueryParams()['detailed'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            ),
        );

        $details = [];

        if ($dto->detailed) {
            $details = [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'uptime' => uptime(),
            ];
        }

        $response = new HealthCheckResponse(
            status: 'ok',
            timestamp: (new \DateTimeImmutable())->format('c'),
            version: $this->getAppVersion(),
            details: $details,
        );

        return new JsonResponse($response, $response->getStatusCode());
    }

    private function getAppVersion(): string
    {
        // You can load this from composer.json or a VERSION file
        return '1.0.0';
    }
}

/**
 * Get system uptime (if available)
 */
function uptime(): ?string
{
    if (PHP_OS_FAMILY === 'Linux') {
        $uptimeData = @file_get_contents('/proc/uptime');
        if ($uptimeData !== false) {
            $uptime = (int) explode(' ', $uptimeData)[0];
            return gmdate('H:i:s', $uptime);
        }
    }
    return null;
}
