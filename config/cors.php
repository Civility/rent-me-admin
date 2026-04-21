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

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],
   
    // 'allowed_origins' => ['*'],
    'allowed_origins' => [ 'http://localhost:3000', 'https://winter.local'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Length', 'X-JSON-Response'],
    // 'exposed_headers' => (array) env('CORS_EXPOSED_HEADERS', []),

    'max_age' => 86400,

    'supports_credentials' => false,

];
