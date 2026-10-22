<?php

declare(strict_types=1);

namespace HealthCheck\Infrastructure\Handler;

use HealthCheck\Application\Request\HealthCheckRequest;
use HealthCheck\Application\Service\HealthCheckService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Health Check Handler (Inbound Adapter)
 *
 * This is an inbound adapter (Infrastructure layer) that handles HTTP requests.
 * It translates the HTTP request into an application request object and the
 * application response back into JSON.
 *
 * In hexagonal architecture:
 * - This handler is in the Infrastructure layer (external concern)
 * - It delegates to the Application layer (HealthCheckService)
 * - The Application layer is independent of HTTP/Infrastructure concerns
 */
final readonly class HealthCheckHandler implements RequestHandlerInterface
{
    public function __construct(
        private HealthCheckService $healthCheckService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dto = new HealthCheckRequest(
            detailed: filter_var(
                $request->getQueryParams()['detailed'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            ),
        );

        $response = $this->healthCheckService->check($dto);

        return new JsonResponse($response, $response->getStatusCode());
    }
}
