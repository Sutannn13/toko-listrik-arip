<?php

$csvToArray = static function (string $value): array {
    return array_values(array_filter(
        array_map(static fn(string $item): string => trim($item), explode(',', $value)),
        static fn(string $item): bool => $item !== ''
    ));
};

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => array_map('strtoupper', $csvToArray((string) env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'))),

    'allowed_origins' => $csvToArray((string) env('CORS_ALLOWED_ORIGINS', 'http://localhost,http://localhost:3000')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => $csvToArray((string) env('CORS_ALLOWED_HEADERS', 'Content-Type,X-Requested-With,Authorization,X-CSRF-TOKEN,Accept,Origin')),

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 0),

    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', true), FILTER_VALIDATE_BOOL),

];
