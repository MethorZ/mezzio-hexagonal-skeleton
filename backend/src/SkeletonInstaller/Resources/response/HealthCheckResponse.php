<?php

declare(strict_types=1);

namespace App\Application\Response;

use MethorZ\Dto\Response\JsonSerializableDto;

/**
 * Health Check Response DTO
 *
 * Example response DTO demonstrating methorz/http-dto automatic JSON serialization.
 */
final readonly class HealthCheckResponse implements JsonSerializableDto
{
    /**
     * @param array<string, mixed>|null $details
     */
    public function __construct(
        public string $name,
        public string $status,
        public string $message,
        public ?array $details = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'name'    => $this->name,
            'status'  => $this->status,
            'message' => $this->message,
        ];

        if ($this->details !== null) {
            $data['details'] = $this->details;
        }

        return $data;
    }

    public function getStatusCode(): int
    {
        return 200; // OK
    }
}

