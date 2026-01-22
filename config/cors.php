<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // IMPORTANT: When using credentials, you CANNOT use '*'. Must specify exact origins.
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:8080,http://localhost:3000,http://127.0.0.1:8080,http://localhost:8000')),

    'allowed_origins_patterns' => [
        // Permite cualquier origen HTTP/HTTPS para evitar bloqueos en producción
        '#^https?://.*$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // MUST be true for Sanctum to work with CSRF cookies
    'supports_credentials' => true,

];
