<?php

$origin = (string) env('CORS_ORIGIN', '');

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS options
    |--------------------------------------------------------------------------
    |
    | Only the frontend origin configured via CORS_ORIGIN is allowed. When it
    | is not set, any origin is accepted so local development keeps working.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origin !== '' ? [$origin] : ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
