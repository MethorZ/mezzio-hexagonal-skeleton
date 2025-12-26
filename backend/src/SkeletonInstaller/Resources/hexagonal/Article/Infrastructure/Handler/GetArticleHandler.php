<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Request\GetArticleRequest;
use Article\Application\Service\ArticleService;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Get Article Handler (Inbound Adapter)
 *
 * Handles GET /articles/{id} requests to retrieve a single article.
 */
final readonly class GetArticleHandler implements DtoHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        GetArticleRequest $dto,
    ): JsonSerializableDto {
        // The $dto->id is automatically populated from the {id} route parameter
        return $this->articleService->get($dto);
    }
}

