<?php

declare(strict_types=1);

namespace Article\Application\Response;

use MethorZ\Dto\Response\JsonSerializableDto;

/**
 * Article List Response DTO
 *
 * Response object for list articles endpoint.
 */
final readonly class ArticleListResponse implements JsonSerializableDto
{
    /**
     * @param array<ArticleResponse> $articles
     */
    public function __construct(
        public array $articles,
        public int $total,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'articles' => array_map(
                fn (ArticleResponse $article) => $article->jsonSerialize(),
                $this->articles,
            ),
            'total' => $this->total,
        ];
    }

    public function getStatusCode(): int
    {
        return 200;
    }
}

