<?php

declare(strict_types=1);

namespace Article\Application\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Get Article Request DTO
 *
 * Request object for retrieving a single article.
 */
final readonly class GetArticleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $id,
    ) {}
}

