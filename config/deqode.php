<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hosts & public scan URLs
    |--------------------------------------------------------------------------
    |
    | Local (Herd): single host deqode.test with scan path prefix /r/{slug}.
    | Production: app host for panels; dedicated scan host for /{slug}.
    |
    */

    'app_host' => env('DEQODE_APP_HOST', 'deqode.test'),

    'scan_host' => env('DEQODE_SCAN_HOST', 'deqode.test'),

    'scan_path_prefix' => env('DEQODE_SCAN_PATH_PREFIX', 'r'),

    'platform_domain' => env('DEQODE_PLATFORM_DOMAIN', 'deqode.test'),

    /*
    |--------------------------------------------------------------------------
    | Sqids (default public Qode codes) — lock after any customer prints codes
    |--------------------------------------------------------------------------
    */

    'sqids' => [
        'alphabet' => env('DEQODE_SQIDS_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
        'min_length' => (int) env('DEQODE_SQIDS_MIN_LENGTH', 8),
    ],

];
