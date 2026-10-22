<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Request\GetArticleRequest;
use Article\Application\Service\ArticleService;
use Laminas\Diactoros\Response\JsonResponse;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Get Article Handler (Inbound Adapter)
 *
 * Handles GET /articles/{id} requests to retrieve a single article.
 */
final readonly class GetArticleHandler implements RequestHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        if (! is_string($id)) {
            throw new LogicException('Route "article.get" must define an {id} parameter');
        }

        $response = $this->articleService->get(new GetArticleRequest(id: $id));

        return new JsonResponse($response, $response->getStatusCode());
    }
}
