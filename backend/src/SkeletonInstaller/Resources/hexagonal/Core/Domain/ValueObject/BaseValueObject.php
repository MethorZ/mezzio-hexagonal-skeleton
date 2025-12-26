<?php

declare(strict_types=1);

namespace Core\Domain\ValueObject;

/**
 * Base Value Object
 *
 * Abstract base class for all immutable value objects in the domain.
 * Value objects are defined by their values, not their identity.
 *
 * Key characteristics:
 * - Immutable: Once created, cannot be changed
 * - Equality by value: Two VOs are equal if their values are equal
 * - Self-validating: Validates its own invariants in constructor
 *
 * @example
 * final readonly class Email extends BaseValueObject
 * {
 *     public function __construct(public string $value)
 *     {
 *         $this->validate();
 *     }
 *
 *     protected function validate(): void
 *     {
 *         if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
 *             throw new InvalidEmailException($this->value);
 *         }
 *     }
 *
 *     protected function getEqualityValues(): array
 *     {
 *         return [$this->value];
 *     }
 * }
 */
abstract readonly class BaseValueObject
{
    /**
     * Validate the value object
     *
     * Override this method to add validation rules.
     * Throw a domain exception if validation fails.
     */
    protected function validate(): void
    {
        // Override in subclasses to add validation
    }

    /**
     * Get values used for equality comparison
     *
     * @return array<mixed> Values that define this VO's identity
     */
    abstract protected function getEqualityValues(): array;

    /**
     * Check equality with another value object
     */
    public function equals(self $other): bool
    {
        if (!$other instanceof static) {
            return false;
        }

        return $this->getEqualityValues() === $other->getEqualityValues();
    }

    /**
     * Get string representation
     */
    public function __toString(): string
    {
        $values = $this->getEqualityValues();

        if (count($values) === 1) {
            return (string) $values[0];
        }

        return json_encode($values, JSON_THROW_ON_ERROR);
    }
}

