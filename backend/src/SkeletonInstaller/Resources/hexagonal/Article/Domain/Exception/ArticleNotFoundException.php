<?php

declare(strict_types=1);

namespace Article\Domain\Exception;

use Article\Domain\ValueObject\ArticleId;
use Core\Domain\Exception\DomainException;

/**
 * Article Not Found Exception
 *
 * Thrown when an article cannot be found by its ID.
 */
final class ArticleNotFoundException extends DomainException
{
    public static function withId(ArticleId $id): self
    {
        return new self("Article with ID '{$id->getValue()}' not found");
    }
}

