<?php

/**
 * Validation Configuration
 *
 * Configure Symfony Validator for request validation.
 */

declare(strict_types=1);

return [
    'validation' => [
        // Enable attribute-based validation (#[Assert\*] annotations)
        'enable_attributes' => true,

        // Translation domain for validation messages
        'translation_domain' => 'validators',
    ],
];
