<?php

declare(strict_types=1);

namespace App\Application\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Health Check Request DTO
 *
 * Example DTO demonstrating methorz/http-dto usage.
 * Query parameters are automatically mapped to this object.
 *
 * Usage: GET /?detailed=true
 */
final readonly class HealthCheckRequest
{
    public function __construct(
        #[Assert\Type('bool')]
        public bool $detailed = false,
    ) {}
}

