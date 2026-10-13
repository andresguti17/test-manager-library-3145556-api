<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manager Library settings
    |--------------------------------------------------------------------------
    |
    | Every secret comes from the environment; only .env.example is versioned
    | (Article IX). No HTTP response ever includes a password or hash.
    |
    */

    'jwt_secret' => env('JWT_SECRET', ''),

    'covers_dir' => env('COVERS_DIR', storage_path('app/covers')),

    'admin_username' => env('ADMIN_USERNAME', ''),

    'admin_password' => env('ADMIN_PASSWORD', ''),

];
