<?php

declare(strict_types=1);

namespace Core\Domain\Exception;

use RuntimeException;

/**
 * Base Domain Exception
 *
 * Base class for all domain-level exceptions.
 * Domain exceptions represent business rule violations.
 *
 * Use these exceptions when:
 * - A business rule is violated
 * - An invariant cannot be maintained
 * - A domain operation cannot be completed
 *
 * These exceptions can be caught by infrastructure error handlers
 * and converted to appropriate HTTP responses (e.g., RFC 7807 Problem Details).
 *
 * @example
 * final class InsufficientFundsException extends DomainException
 * {
 *     public function __construct(float $requested, float $available)
 *     {
 *         parent::__construct(
 *             sprintf('Cannot withdraw %.2f, only %.2f available', $requested, $available),
 *             'insufficient-funds'
 *         );
 *     }
 * }
 */
class DomainException extends RuntimeException
{
    /**
     * @param string $message Human-readable error message
     * @param string $errorCode Machine-readable error code (for API responses)
     * @param array<string, mixed> $context Additional context for logging/debugging
     */
    public function __construct(
        string $message,
        private readonly string $errorCode = 'domain-error',
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    /**
     * Get machine-readable error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get additional context
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get HTTP status code for this exception
     *
     * Override in subclasses to return appropriate HTTP codes.
     * Default is 400 Bad Request (business rule violation).
     */
    public function getStatusCode(): int
    {
        return 400;
    }
}

