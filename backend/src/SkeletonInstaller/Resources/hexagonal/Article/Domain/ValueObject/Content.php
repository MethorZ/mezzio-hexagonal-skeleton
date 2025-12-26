<?php

declare(strict_types=1);

namespace Article\Domain\ValueObject;

use Article\Domain\Exception\InvalidArticleException;
use Core\Domain\ValueObject\BaseValueObject;

/**
 * Content Value Object
 *
 * Represents article content with domain validation.
 */
final readonly class Content extends BaseValueObject
{
    private const MIN_LENGTH = 10;
    private const MAX_LENGTH = 100000;

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

    /**
     * Get content excerpt (first N characters)
     */
    public function getExcerpt(int $length = 200): string
    {
        if (mb_strlen($this->value) <= $length) {
            return $this->value;
        }

        return mb_substr($this->value, 0, $length) . '...';
    }

    protected function getEqualityValues(): array
    {
        return [$this->value];
    }

    protected function validate(): void
    {
        $length = mb_strlen(trim($this->value));

        if ($length < self::MIN_LENGTH) {
            throw InvalidArticleException::contentTooShort(self::MIN_LENGTH);
        }

        if ($length > self::MAX_LENGTH) {
            throw InvalidArticleException::contentTooLong(self::MAX_LENGTH);
        }
    }
}

