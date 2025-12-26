<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Service\ArticleService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Delete Article Handler (Inbound Adapter)
 *
 * Handles DELETE /articles/{id} requests to delete an article.
 * Note: This uses standard PSR-15 handler since DELETE typically has no request body.
 */
final readonly class DeleteArticleHandler implements RequestHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $this->articleService->delete($id);

        return new EmptyResponse(204);
    }
}

