<?php

declare(strict_types=1);

namespace HealthCheck\Application\Response;

use JsonSerializable;

/**
 * Health Check Response DTO
 *
 * Response object serialized to JSON by the inbound adapter.
 */
final readonly class HealthCheckResponse implements JsonSerializable
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public string $status,
        public string $timestamp,
        public ?string $version = null,
        public array $details = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'status' => $this->status,
            'timestamp' => $this->timestamp,
        ];

        if ($this->version !== null) {
            $data['version'] = $this->version;
        }

        if (!empty($this->details)) {
            $data['details'] = $this->details;
        }

        return $data;
    }

    public function getStatusCode(): int
    {
        return 200;
    }
}
