<?php

declare(strict_types=1);

namespace Article\Infrastructure\Handler;

use Article\Application\Request\CreateArticleRequest;
use Article\Application\Service\ArticleService;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Create Article Handler (Inbound Adapter)
 *
 * Handles POST /articles requests to create new articles.
 */
final readonly class CreateArticleHandler implements DtoHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        CreateArticleRequest $dto,
    ): JsonSerializableDto {
        return $this->articleService->create($dto);
    }
}

