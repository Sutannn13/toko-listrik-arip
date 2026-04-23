<?php

return [
    'headers_enabled' => filter_var(env('SECURITY_HEADERS_ENABLED', true), FILTER_VALIDATE_BOOL),

    'content_security_policy' => env(
        'SECURITY_CSP',
        "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' http://127.0.0.1:* http://localhost:*; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com http://127.0.0.1:* http://localhost:*; img-src 'self' data: https: http://127.0.0.1:* http://localhost:*; font-src 'self' data: https://fonts.gstatic.com https://fonts.googleapis.com; connect-src 'self' ws://127.0.0.1:* http://127.0.0.1:* ws://localhost:* http://localhost:*;"
    ),

    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
];
