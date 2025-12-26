<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Request\UpdateArticleRequest;
use Article\Application\Service\ArticleService;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Update Article Handler (Inbound Adapter)
 *
 * Handles PUT /articles/{id} requests to update an article.
 */
final readonly class UpdateArticleHandler implements DtoHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        UpdateArticleRequest $dto,
    ): JsonSerializableDto {
        // The $dto->id is automatically populated from the {id} route parameter
        // Body params (title, content) are merged with route params
        return $this->articleService->update($dto);
    }
}

