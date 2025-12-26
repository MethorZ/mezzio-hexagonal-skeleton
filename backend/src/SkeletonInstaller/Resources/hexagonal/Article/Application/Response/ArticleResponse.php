<?php

declare(strict_types=1);

namespace Article\Application\Response;

use Article\Domain\Entity\Article;
use MethorZ\Dto\Response\JsonSerializableDto;

/**
 * Article Response DTO
 *
 * Response object for article endpoints.
 * Automatically serialized to JSON by methorz/http-dto.
 */
final readonly class ArticleResponse implements JsonSerializableDto
{
    private function __construct(
        public string $id,
        public string $title,
        public string $content,
        public string $author,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?string $publishedAt = null,
    ) {}

    /**
     * Create from domain entity
     */
    public static function fromEntity(Article $article): self
    {
        return new self(
            id: $article->getId()->getValue(),
            title: $article->getTitle()->getValue(),
            content: $article->getContent()->getValue(),
            author: $article->getAuthor()->getValue(),
            status: $article->getStatus()->value,
            createdAt: $article->getCreatedAt()->format('c'),
            updatedAt: $article->getUpdatedAt()->format('c'),
            publishedAt: $article->getPublishedAt()?->format('c'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->author,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];

        if ($this->publishedAt !== null) {
            $data['publishedAt'] = $this->publishedAt;
        }

        return $data;
    }

    public function getStatusCode(): int
    {
        return 200;
    }
}

