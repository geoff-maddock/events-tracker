<?php

/*
|--------------------------------------------------------------------------
| Content Security Policy (report-only)
|--------------------------------------------------------------------------
|
| Sent as Content-Security-Policy-Report-Only by App\Http\Middleware\SecurityHeaders
| (#2166): browsers report violations to /csp-report but block nothing, so the
| policy can be tuned from real reports before it is enforced.
|
| 'unsafe-inline' (scripts and styles) and 'unsafe-eval' (Alpine) are needed by
| the current views; removing them is the follow-up once inline scripts move to
| files. The tightening that matters today is object-src, base-uri, form-action
| and frame-ancestors, plus knowing which hosts the pages really load from.
|
*/

return [

    'enabled' => env('CSP_REPORT_ONLY', true),

    'directives' => [
        'default-src' => ["'self'"],
        'script-src' => [
            "'self'", "'unsafe-inline'", "'unsafe-eval'",
            'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com',
            'https://www.googletagmanager.com', 'https://www.google-analytics.com',
        ],
        'style-src' => [
            "'self'", "'unsafe-inline'",
            'https://fonts.googleapis.com', 'https://fonts.bunny.net',
            'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com',
        ],
        'font-src' => [
            "'self'", 'data:',
            'https://fonts.gstatic.com', 'https://fonts.bunny.net',
            'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com',
        ],
        // event, venue and link images come from the Spaces bucket and many third-party sites
        'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
        'connect-src' => [
            "'self'",
            // GA4 also sends hits to www.google.com
            'https://www.google-analytics.com', 'https://*.google-analytics.com', 'https://www.googletagmanager.com', 'https://www.google.com',
            // CSP wildcards may only lead the host, so ws-*.pusher.com must be *.pusher.com
            'wss://*.pusher.com', 'https://*.pusher.com',
        ],
        'frame-src' => [
            'https://bandcamp.com', 'https://w.soundcloud.com',
            'https://www.youtube.com', 'https://www.youtube-nocookie.com',
            'https://www.instagram.com', 'https://www.googletagmanager.com',
        ],
        'media-src' => ["'self'", 'https:'],
        'object-src' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'self'"],
    ],

];
