<?php

declare(strict_types=1);

namespace Article\Domain\ValueObject;

use Core\Domain\ValueObject\BaseValueObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Article ID Value Object
 *
 * Represents a unique identifier for an article using UUID.
 */
final readonly class ArticleId extends BaseValueObject
{
    private UuidInterface $uuid;

    private function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Create a new unique article ID
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create from existing UUID string
     */
    public static function fromString(string $uuid): self
    {
        return new self(Uuid::fromString($uuid));
    }

    public function getValue(): string
    {
        return $this->uuid->toString();
    }

    public function equals(mixed $other): bool
    {
        return $other instanceof self && $this->uuid->equals($other->uuid);
    }

    protected function getEqualityValues(): array
    {
        return [$this->uuid->toString()];
    }

    protected function validate(): void
    {
        // UUID validation is handled by Uuid::fromString() which throws exception on invalid input
        // No additional validation needed
    }
}

