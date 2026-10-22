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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $status = $request->getQueryParams()['status'] ?? null;

        $response = $this->articleService->list(is_string($status) ? $status : null);

        return new JsonResponse(
            $response->jsonSerialize(),
            $response->getStatusCode(),
        );
    }
}
