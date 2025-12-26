<?php

declare(strict_types=1);

namespace Article\Domain\ValueObject;

use Article\Domain\Exception\InvalidArticleException;
use Core\Domain\ValueObject\BaseValueObject;

/**
 * Author Value Object
 *
 * Represents an article author name.
 */
final readonly class Author extends BaseValueObject
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 100;

    private function __construct(
        private string $value,
    ) {
        $this->validate();
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(mixed $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    protected function getEqualityValues(): array
    {
        return [$this->value];
    }

    protected function validate(): void
    {
        $length = mb_strlen(trim($this->value));

        if ($length < self::MIN_LENGTH) {
            throw InvalidArticleException::authorNameTooShort(self::MIN_LENGTH);
        }

        if ($length > self::MAX_LENGTH) {
            throw InvalidArticleException::authorNameTooLong(self::MAX_LENGTH);
        }
    }
}

