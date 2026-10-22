<?php

declare(strict_types=1);

namespace Article\Application\Request;

/**
 * Create Article Request DTO
 *
 * Request object for creating a new article.
 * Field rules are enforced by the domain value objects.
 */
final readonly class CreateArticleRequest
{
    public function __construct(
        public string $title,
        public string $content,
        public string $author,
    ) {
    }
}
