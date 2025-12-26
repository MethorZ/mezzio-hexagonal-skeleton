<?php

declare(strict_types=1);

namespace Article\Domain\Enum;

/**
 * Article Status Enum
 *
 * Represents the lifecycle status of an article in the domain.
 */
enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if article can be edited
     */
    public function canEdit(): bool
    {
        return $this !== self::ARCHIVED;
    }
}

