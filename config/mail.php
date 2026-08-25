<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | MAIL_MAILER is the modern name. MAIL_DRIVER is read as a fallback so an
    | environment file written against the old flat config keeps working during
    | the SES cutover; drop that fallback once every environment sets
    | MAIL_MAILER.
    |
    | This file used to be the pre-Laravel-6 flat format (a top-level "driver"
    | key, with host/port/username/password beside it). In that format
    | MailManager::getConfig() hands the *entire* config array to the transport
    | factory, which is harmless for SMTP but means the SES factory would have
    | received driver/host/port/from/markdown as SesClient constructor
    | arguments. Hence the rewrite.
    |
    | Do not reintroduce a top-level "driver" key. Its presence at any value
    | puts MailManager back on that legacy branch and silently ignores the
    | "mailers" array below.
    |
    */

    'default' => env('MAIL_MAILER', env('MAIL_DRIVER', 'smtp')),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Credentials for "ses" live in config/services.php under the key of the
    | same name, which is where MailManager::createSesV2Transport() reads them.
    |
    */

    'mailers' => [
        'ses' => [
            // The SES v2 SendEmail API rather than the v1 SendRawEmail one.
            'transport' => 'ses-v2',
        ],

        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            // No "encryption" key: since Laravel 11 the SMTP transport derives
            // its scheme from "scheme", or from the port (465 = smtps,
            // anything else = smtp with STARTTLS). The flat config's
            // hardcoded 'encryption' => 'tls' had already stopped doing
            // anything. Set MAIL_SCHEME only if a server needs it forced.
            'scheme' => env('MAIL_SCHEME'),
        ],

        // Credentials read from config/services.php "mailgun" (MAIL_DOMAIN /
        // MAIL_SECRET).
        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | Under SES this address is not merely cosmetic: its domain must be a
    | verified identity in the sending region or the API rejects the message.
    |
    */

    'from' => ['address' => env('APP_NOREPLY_EMAIL', 'postmaster@localhost'), 'name' => env('APP_NAME', 'Events Admin')],

    /*
     * Markdown Config
     */
    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
];
