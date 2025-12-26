<?php

declare(strict_types=1);

namespace Article\Application\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Create Article Request DTO
 *
 * Request object for creating a new article.
 * Automatically validated by methorz/http-dto using Symfony Validator.
 */
final readonly class CreateArticleRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(min: 3, max: 200)]
        public string $title,

        #[Assert\NotBlank(message: 'Content is required')]
        #[Assert\Length(min: 10)]
        public string $content,

        #[Assert\NotBlank(message: 'Author is required')]
        #[Assert\Length(min: 2, max: 100)]
        public string $author,
    ) {}
}

