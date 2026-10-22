<?php

declare(strict_types=1);

namespace Article\Application\Request;

/**
 * Update Article Request DTO
 *
 * Request object for updating an existing article.
 * Field rules are enforced by the domain value objects.
 */
final readonly class UpdateArticleRequest
{
    public function __construct(
        public string $id,
        public string $title,
        public string $content,
    ) {
    }
}
