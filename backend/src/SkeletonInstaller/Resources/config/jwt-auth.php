<?php

declare(strict_types=1);

return [
    'jwt_auth' => [
        'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
        'secret' => $_ENV['JWT_SECRET'] ?? null,
        'public_key_path' => $_ENV['JWT_PUBLIC_KEY_PATH'] ?? null,
        'issuer' => $_ENV['JWT_ISSUER'] ?? null,
        'audience' => $_ENV['JWT_AUDIENCE'] ?? null,
        'header_name' => $_ENV['JWT_HEADER_NAME'] ?? 'Authorization',
        'header_prefix' => $_ENV['JWT_HEADER_PREFIX'] ?? 'Bearer',
    ],
];

