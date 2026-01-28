<?php

/**
 * Copyright (c) Vincent Klaiber.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/vinkla/laravel-shield
 */

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Basic Auth Credentials
    |--------------------------------------------------------------------------
    |
    | The array of users with hashed username and password credentials which are
    | used when logging in with HTTP basic authentication.
    |
    | Shield is intended for API protection only, not for protecting the web
    | application routes. Only enable this if you explicitly apply the 'shield'
    | middleware to specific API routes.
    |
    */

    'users' => [
        // Shield is disabled by default for web routes
        // Only applies when explicitly used with ->middleware('shield')
        'default' => [
            env('SHIELD_USER'),
            env('SHIELD_PASSWORD'),
        ],
    ],

];
