<?php

declare(strict_types=1);

namespace HealthCheck\Infrastructure\Handler;

use HealthCheck\Application\Request\HealthCheckRequest;
use HealthCheck\Application\Service\HealthCheckService;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Health Check Handler (Inbound Adapter)
 *
 * This is an inbound adapter (Infrastructure layer) that handles HTTP requests.
 * It uses methorz/http-dto for automatic request/response handling.
 *
 * In hexagonal architecture:
 * - This handler is in the Infrastructure layer (external concern)
 * - It delegates to the Application layer (HealthCheckService)
 * - The Application layer is independent of HTTP/Infrastructure concerns
 *
 * The DtoHandlerWrapper automatically:
 * - Maps query parameters to HealthCheckRequest DTO
 * - Validates the DTO (if validation attributes are present)
 * - Calls this handler with the validated DTO
 * - Serializes HealthCheckResponse to JSON
 */
final readonly class HealthCheckHandler implements DtoHandlerInterface
{
    public function __construct(
        private HealthCheckService $healthCheckService,
    ) {}

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
        // Delegate to application service
        return $this->healthCheckService->check($dto);
    }
}

