<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Discord auto-repost (issue #2058)
    |--------------------------------------------------------------------------
    |
    | Master switch for the entire feature. When false: no listener queues a
    | post, no scheduled command sends anything, and the poster refuses even a
    | manual push. Deploy with this off, point one target at a private staging
    | channel, then flip it.
    |
    | Always read these through config(), never env() — production caches the
    | config and env() returns null outside of config files.
    |
    | Per-channel webhook URLs are NOT configured here. They are secrets,
    | managed in the admin UI at /discord-targets and stored encrypted.
    |
    */

    'enabled' => env('DISCORD_ENABLED', false),

    /*
    | HTTP behaviour for webhook calls.
    |
    | max_retry_sleep_seconds caps how long the client will sit on a 429 in
    | process. Past that it throws retryable and lets the queue back off —
    | a worker must never be parked for a minute waiting on rate limits.
    */
    'timeout' => env('DISCORD_TIMEOUT', 10),
    'max_retries' => 3,
    'max_retry_sleep_seconds' => 10,

    /*
    | Settle delay between an event gaining a flyer and its announcement post.
    | Admins routinely fix the title, venue or image within a few minutes of
    | creating an event; posting instantly would broadcast the typos.
    */
    'create_delay_minutes' => env('DISCORD_CREATE_DELAY_MINUTES', 30),

    /*
    | Reminder posts. Offsets are days before start_at, and are per-target —
    | these are the defaults applied when a target does not set its own.
    |
    | reminder_min_gap_hours suppresses a reminder that would land right on
    | top of a recent post for the same event and target (e.g. an event
    | created at T-8d being announced, then reminded at T-7d).
    */
    'default_reminder_offsets' => [7, 1],
    'reminder_min_gap_hours' => 36,

    /*
    | Weekly digest defaults. day is a Carbon dayOfWeek (0 = Sunday, 4 = Thursday),
    | hour is 0-23 in the app timezone. max_events caps a single roundup.
    */
    'digest' => [
        'default_day' => 4,
        'default_hour' => 10,
        'window_days' => 7,
        'max_events' => 25,
    ],

    /*
    | Webhook presentation defaults, overridable per target.
    */
    'embed_color' => 0x7B2FF7,
    'username' => env('DISCORD_WEBHOOK_USERNAME'),
    'avatar_url' => env('DISCORD_WEBHOOK_AVATAR_URL'),

    /*
    | Target health. Only *permanent* failures count toward the threshold —
    | counting transient errors would disable every channel at once during a
    | Discord outage. A deleted webhook (401/403/404) disables immediately,
    | regardless of this number.
    */
    'auto_disable_after_failures' => env('DISCORD_AUTO_DISABLE_AFTER_FAILURES', 5),
    'notify_admin_on_failure' => env('DISCORD_NOTIFY_ADMIN_ON_FAILURE', true),

    /*
    | Discord's documented embed limits. Every string the embed builder emits
    | is clamped against these — Discord answers an over-long or empty field
    | with an opaque 400 rather than a useful message.
    */
    'limits' => [
        'title' => 256,
        'description' => 4096,
        'field_name' => 256,
        'field_value' => 1024,
        'footer' => 2048,
        'fields' => 25,
        'embeds' => 10,
        'total' => 6000,
    ],

];
