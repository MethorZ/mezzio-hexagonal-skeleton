<?php

declare(strict_types=1);

namespace Article\Infrastructure\Repository;

use Article\Domain\Entity\Article;
use Article\Domain\Enum\ArticleStatus;
use Article\Domain\Port\ArticleRepositoryInterface;
use Article\Domain\ValueObject\ArticleId;

/**
 * In-Memory Article Repository (Outbound Adapter)
 *
 * Simple in-memory implementation for development and testing.
 * Data is lost when the process ends.
 *
 * For production, use DbArticleRepository with methorz/swift-db.
 */
final class InMemoryArticleRepository implements ArticleRepositoryInterface
{
    /**
     * @var array<string, Article>
     */
    private static array $articles = [];

    public function save(Article $article): void
    {
        self::$articles[$article->getId()->getValue()] = $article;
    }

    public function findById(ArticleId $id): ?Article
    {
        return self::$articles[$id->getValue()] ?? null;
    }

    /**
     * @return array<Article>
     */
    public function findAll(): array
    {
        return array_values(self::$articles);
    }

    /**
     * @return array<Article>
     */
    public function findByStatus(ArticleStatus $status): array
    {
        return array_values(
            array_filter(
                self::$articles,
                fn (Article $article) => $article->getStatus() === $status,
            ),
        );
    }

    public function delete(ArticleId $id): void
    {
        unset(self::$articles[$id->getValue()]);
    }

    public function exists(ArticleId $id): bool
    {
        return isset(self::$articles[$id->getValue()]);
    }

    /**
     * Clear all articles (useful for testing)
     */
    public static function clear(): void
    {
        self::$articles = [];
    }
}

