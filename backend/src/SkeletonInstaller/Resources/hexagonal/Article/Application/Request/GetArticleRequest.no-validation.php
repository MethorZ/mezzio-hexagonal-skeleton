<?php

declare(strict_types=1);

namespace Article\Application\Request;

/**
 * Get Article Request DTO
 *
 * Request object for retrieving a single article.
 * Field rules are enforced by the domain value objects.
 */
final readonly class GetArticleRequest
{
    public function __construct(
        public string $id,
    ) {
    }
}
