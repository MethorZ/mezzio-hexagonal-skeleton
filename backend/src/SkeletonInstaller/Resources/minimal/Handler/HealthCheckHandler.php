<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Request\HealthCheckRequest;
use App\Application\Response\HealthCheckResponse;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Health Check Handler
 *
 * Simple health check endpoint using methorz/http-dto for automatic
 * request/response handling.
 *
 * The DtoHandlerWrapper automatically:
 * - Maps query parameters to HealthCheckRequest DTO
 * - Validates the DTO (if validation attributes are present)
 * - Calls this handler with the validated DTO
 * - Serializes HealthCheckResponse to JSON
 */
final readonly class HealthCheckHandler implements DtoHandlerInterface
{
    /**
     * Handle health check request
     *
     * @param ServerRequestInterface $request The original PSR-7 request
     * @param HealthCheckRequest $dto Validated request DTO (auto-injected)
     * @return JsonSerializableDto Response DTO (auto-serialized to JSON)
     */
    public function __invoke(
        ServerRequestInterface $request,
        HealthCheckRequest $dto,
    ): JsonSerializableDto {
        $details = [];

        if ($dto->detailed) {
            $details = [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'uptime' => uptime(),
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

