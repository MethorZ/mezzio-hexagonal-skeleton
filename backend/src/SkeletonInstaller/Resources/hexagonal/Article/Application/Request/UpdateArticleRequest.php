<?php

declare(strict_types=1);

namespace Article\Application\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Update Article Request DTO
 *
 * Request object for updating an existing article.
 */
final readonly class UpdateArticleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $id,

        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(min: 3, max: 200)]
        public string $title,

        #[Assert\NotBlank(message: 'Content is required')]
        #[Assert\Length(min: 10)]
        public string $content,
    ) {}
}

