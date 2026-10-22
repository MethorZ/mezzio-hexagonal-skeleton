<?php

declare(strict_types=1);

namespace Article\Infrastructure\Http;

use JsonException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON Request Body Reader
 *
 * Extracts string fields from a JSON request body for the plain PSR-15 handlers.
 * Install methorz/http-dto to map request bodies to DTOs automatically instead.
 */
final class JsonBody
{
    /**
     * @param list<string> $fields
     * @return array<string, string>|null Null when the body is not a JSON object
     *                                    or a field is missing or not a string
     */
    public static function stringFields(ServerRequestInterface $request, array $fields): ?array
    {
        try {
            $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($body)) {
            return null;
        }

        $values = [];

        foreach ($fields as $field) {
            $value = $body[$field] ?? null;

            if (! is_string($value)) {
                return null;
            }

            $values[$field] = $value;
        }

        return $values;
    }
}
