<?php

declare(strict_types=1);

namespace Article\Domain\Entity;

use Article\Domain\Enum\ArticleStatus;
use Article\Domain\Exception\InvalidArticleException;
use Article\Domain\ValueObject\ArticleId;
use Article\Domain\ValueObject\Author;
use Article\Domain\ValueObject\Content;
use Article\Domain\ValueObject\Title;
use DateTimeImmutable;

/**
 * Article Aggregate Root
 *
 * Represents an article in the domain with all business logic and invariants.
 * This is the main entity in the Article bounded context.
 */
final class Article
{
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $publishedAt = null;

    private function __construct(
        private readonly ArticleId $id,
        private Title $title,
        private Content $content,
        private Author $author,
        private ArticleStatus $status,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Create a new article in draft status
     */
    public static function create(
        ArticleId $id,
        Title $title,
        Content $content,
        Author $author,
    ): self {
        return new self(
            id: $id,
            title: $title,
            content: $content,
            author: $author,
            status: ArticleStatus::DRAFT,
        );
    }

    /**
     * Reconstitute an article from persistence
     */
    public static function reconstitute(
        ArticleId $id,
        Title $title,
        Content $content,
        Author $author,
        ArticleStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $publishedAt = null,
    ): self {
        $article = new self($id, $title, $content, $author, $status);
        $article->createdAt = $createdAt;
        $article->updatedAt = $updatedAt;
        $article->publishedAt = $publishedAt;

        return $article;
    }

    /**
     * Update article content
     */
    public function update(Title $title, Content $content): void
    {
        if (!$this->status->canEdit()) {
            throw InvalidArticleException::cannotEditArchivedArticle();
        }

        $this->title = $title;
        $this->content = $content;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Publish the article
     */
    public function publish(): void
    {
        if ($this->status === ArticleStatus::PUBLISHED) {
            return; // Already published
        }

        $this->status = ArticleStatus::PUBLISHED;
        $this->publishedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Archive the article
     */
    public function archive(): void
    {
        $this->status = ArticleStatus::ARCHIVED;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters

    public function getId(): ArticleId
    {
        return $this->id;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function getStatus(): ArticleStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }
}

