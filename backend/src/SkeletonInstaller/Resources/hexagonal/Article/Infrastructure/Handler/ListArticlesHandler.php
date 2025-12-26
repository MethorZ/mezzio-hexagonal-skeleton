<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Service\ArticleService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * List Articles Handler (Inbound Adapter)
 *
 * Handles GET /articles requests to list all articles.
 * Supports optional ?status={draft|published|archived} query parameter.
 */
final readonly class ListArticlesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $status = $queryParams['status'] ?? null;

        $response = $this->articleService->list($status);

        return new JsonResponse(
            $response->jsonSerialize(),
            $response->getStatusCode(),
        );
    }
}

