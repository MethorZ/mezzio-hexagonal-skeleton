<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Request\HealthCheckRequest;
use App\Application\Response\HealthCheckResponse;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Home Handler - Default handler demonstrating methorz/http-dto usage
 *
 * Provides a health check endpoint with automatic DTO request/response handling.
 * - Request DTO is automatically mapped and validated
 * - Response DTO is automatically serialized to JSON
 *
 * Usage: GET / or GET /?detailed=true
 */
final readonly class HomeHandler implements DtoHandlerInterface
{
    /**
     * Handle the request with automatic DTO injection
     *
     * The HealthCheckRequest DTO is automatically:
     * - Mapped from query parameters
     * - Validated using Symfony constraints
     * - Injected as a typed parameter
     */
    public function __invoke(
        ServerRequestInterface $request,
        HealthCheckRequest $dto,
    ): JsonSerializableDto {
        $details = null;

        // Add optional details if requested via DTO
        if ($dto->detailed) {
            $details = [
                'timestamp' => date('c'),
                'version'   => '1.0.0',
                'php'       => PHP_VERSION,
                'env'       => $_ENV['APP_ENV'] ?? 'unknown',
            ];
        }

        return new HealthCheckResponse(
            name: 'Health Check API',
            status: 'healthy',
            message: 'The application is running and ready to build!',
            details: $details,
        );
    }
}
