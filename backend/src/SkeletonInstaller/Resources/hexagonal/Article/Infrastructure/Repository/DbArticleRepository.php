<?php

declare(strict_types=1);

namespace Article\Infrastructure\Repository;

use Article\Domain\Entity\Article;
use Article\Domain\Enum\ArticleStatus;
use Article\Domain\Port\ArticleRepositoryInterface;
use Article\Domain\ValueObject\ArticleId;
use Article\Domain\ValueObject\Author;
use Article\Domain\ValueObject\Content;
use Article\Domain\ValueObject\Title;
use DateTimeImmutable;
use MethorZ\SwiftDb\Connection\Connection;
use MethorZ\SwiftDb\Query\QueryBuilder;

/**
 * Database Article Repository (Outbound Adapter)
 *
 * Production implementation using methorz/swift-db for database operations.
 *
 * Database Schema:
 * CREATE TABLE articles (
 *     id BIGINT AUTO_INCREMENT PRIMARY KEY,
 *     uuid VARCHAR(36) NOT NULL UNIQUE,
 *     title VARCHAR(200) NOT NULL,
 *     content TEXT NOT NULL,
 *     author VARCHAR(100) NOT NULL,
 *     status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
 *     updated_at TIMESTAMP(3) NULL ON UPDATE CURRENT_TIMESTAMP(3),
 *     created_at TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
 *     INDEX idx_status (status),
 *     INDEX idx_author (author)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * Note: Domain lookups use 'uuid' column, 'id' is internal database identifier.
 *
 * To use this repository:
 * 1. Update Article\Application\Config\ConfigProvider to alias this repository:
 *    ArticleRepositoryInterface::class => DbArticleRepository::class
 * 2. Run migrations to create the table
 */
final readonly class DbArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function save(Article $article): void
    {
        $data = $this->toRow($article);

        // Upsert: insert or update if exists
        $exists = $this->exists($article->getId());

        if ($exists) {
            $this->query()
                ->table('articles')
                ->where('uuid', '=', $article->getId()->getValue())
                ->update($data);
        } else {
            $this->query()
                ->table('articles')
                ->insert($data);
        }
    }

    public function findById(ArticleId $id): ?Article
    {
        $row = $this->query()
            ->table('articles')
            ->where('uuid', '=', $id->getValue())
            ->first();

        return $row !== null ? $this->fromRow($row) : null;
    }

    /**
     * @return array<Article>
     */
    public function findAll(): array
    {
        $rows = $this->query()
            ->table('articles')
            ->orderBy('created_at', 'DESC')
            ->get();

        return array_map(
            fn (array $row) => $this->fromRow($row),
            $rows,
        );
    }

    /**
     * @return array<Article>
     */
    public function findByStatus(ArticleStatus $status): array
    {
        $rows = $this->query()
            ->table('articles')
            ->where('status', '=', $status->value)
            ->orderBy('created_at', 'DESC')
            ->get();

        return array_map(
            fn (array $row) => $this->fromRow($row),
            $rows,
        );
    }

    public function delete(ArticleId $id): void
    {
        $this->query()
            ->table('articles')
            ->where('uuid', '=', $id->getValue())
            ->delete();
    }

    public function exists(ArticleId $id): bool
    {
        $count = $this->query()
            ->table('articles')
            ->where('uuid', '=', $id->getValue())
            ->count();

        return $count > 0;
    }

    /**
     * Create a new query builder instance
     */
    private function query(): QueryBuilder
    {
        return new QueryBuilder($this->connection);
    }

    /**
     * Convert domain entity to database row
     *
     * @return array<string, mixed>
     */
    private function toRow(Article $article): array
    {
        return [
            'uuid' => $article->getId()->getValue(),
            'title' => $article->getTitle()->getValue(),
            'content' => $article->getContent()->getValue(),
            'author' => $article->getAuthor()->getValue(),
            'status' => $article->getStatus()->value,
            'published_at' => $article->getPublishedAt()?->format('Y-m-d H:i:s.v'),
            // created_at and updated_at are auto-managed by MySQL
        ];
    }

    /**
     * Convert database row to domain entity
     *
     * @param array<string, mixed> $row
     */
    private function fromRow(array $row): Article
    {
        return Article::reconstitute(
            id: ArticleId::fromString($row['uuid']),
            title: Title::fromString($row['title']),
            content: Content::fromString($row['content']),
            author: Author::fromString($row['author']),
            status: ArticleStatus::from($row['status']),
            createdAt: new DateTimeImmutable($row['created_at']),
            updatedAt: new DateTimeImmutable($row['updated_at']),
            publishedAt: $row['published_at'] !== null
                ? new DateTimeImmutable($row['published_at'])
                : null,
        );
    }
}

