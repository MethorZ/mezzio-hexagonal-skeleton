<?php

declare(strict_types=1);

namespace Article\Domain\Exception;

use Core\Domain\Exception\DomainException;

/**
 * Invalid Article Exception
 *
 * Thrown when article validation rules are violated.
 */
final class InvalidArticleException extends DomainException
{
    public static function titleTooShort(int $minLength): self
    {
        return new self("Article title must be at least {$minLength} characters long");
    }

    public static function titleTooLong(int $maxLength): self
    {
        return new self("Article title cannot exceed {$maxLength} characters");
    }

    public static function contentTooShort(int $minLength): self
    {
        return new self("Article content must be at least {$minLength} characters long");
    }

    public static function contentTooLong(int $maxLength): self
    {
        return new self("Article content cannot exceed {$maxLength} characters");
    }

    public static function authorNameTooShort(int $minLength): self
    {
        return new self("Author name must be at least {$minLength} characters long");
    }

    public static function authorNameTooLong(int $maxLength): self
    {
        return new self("Author name cannot exceed {$maxLength} characters");
    }

    public static function cannotEditArchivedArticle(): self
    {
        return new self('Cannot edit an archived article');
    }
}

