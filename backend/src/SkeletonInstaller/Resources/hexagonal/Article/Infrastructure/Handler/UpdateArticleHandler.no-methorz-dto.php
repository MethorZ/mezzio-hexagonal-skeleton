<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Request\UpdateArticleRequest;
use Article\Application\Service\ArticleService;
use Article\Infrastructure\Http\JsonBody;
use Laminas\Diactoros\Response\JsonResponse;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Update Article Handler (Inbound Adapter)
 *
 * Handles PUT /articles/{id} requests to update an article.
 */
final readonly class UpdateArticleHandler implements RequestHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        if (! is_string($id)) {
            throw new LogicException('Route "article.update" must define an {id} parameter');
        }

        $fields = JsonBody::stringFields($request, ['title', 'content']);

        if ($fields === null) {
            return new JsonResponse(
                ['error' => 'Request body must be a JSON object with string fields "title" and "content"'],
                400,
            );
        }

        $response = $this->articleService->update(new UpdateArticleRequest(
            id: $id,
            title: $fields['title'],
            content: $fields['content'],
        ));

        return new JsonResponse($response, $response->getStatusCode());
    }
}
