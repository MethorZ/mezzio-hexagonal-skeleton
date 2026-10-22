<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Request\CreateArticleRequest;
use Article\Application\Service\ArticleService;
use Article\Infrastructure\Http\JsonBody;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Create Article Handler (Inbound Adapter)
 *
 * Handles POST /articles requests to create new articles.
 */
final readonly class CreateArticleHandler implements RequestHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $fields = JsonBody::stringFields($request, ['title', 'content', 'author']);

        if ($fields === null) {
            return new JsonResponse(
                ['error' => 'Request body must be a JSON object with string fields "title", "content" and "author"'],
                400,
            );
        }

        $response = $this->articleService->create(new CreateArticleRequest(
            title: $fields['title'],
            content: $fields['content'],
            author: $fields['author'],
        ));

        return new JsonResponse($response, $response->getStatusCode());
    }
}
