<?php

declare(strict_types=1);

namespace Article\Domain\Port;

use Article\Domain\Entity\Article;
use Article\Domain\Enum\ArticleStatus;
use Article\Domain\ValueObject\ArticleId;

/**
 * Article Repository Interface (Outbound Port)
 *
 * Defines the contract for article persistence.
 * This is a domain interface implemented by infrastructure adapters.
 */
interface ArticleRepositoryInterface
{
    /**
     * Save an article (create or update)
     */
    public function save(Article $article): void;

    /**
     * Find an article by ID
     */
    public function findById(ArticleId $id): ?Article;

    /**
     * Get all articles
     *
     * @return array<Article>
     */
    public function findAll(): array;

    /**
     * Find articles by status
     *
     * @return array<Article>
     */
    public function findByStatus(ArticleStatus $status): array;

    /**
     * Delete an article
     */
    public function delete(ArticleId $id): void;

    /**
     * Check if an article exists
     */
    public function exists(ArticleId $id): bool;
}

