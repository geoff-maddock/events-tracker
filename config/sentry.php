<?php

return array(
    // Prefer SENTRY_DSN, fall back to the package's default SENTRY_LARAVEL_DSN key
    // so local/.env setups using either name resolve correctly.
    'dsn' => env('SENTRY_DSN', env('SENTRY_LARAVEL_DSN')),

    // The deployed commit. bin/deploy sets SENTRY_RELEASE when it caches the
    // config, so each error is tagged with the deploy that was live.
    'release' => env('SENTRY_RELEASE'),

    // Query values (email addresses, tokens in where clauses) are not sent
    // with errors unless this is turned on to chase a specific problem.
    'breadcrumbs' => [
        'sql_bindings' => (bool) env('SENTRY_SQL_BINDINGS', false),
    ],

    // Capture default user context
   //  'user_context' => true,

    // Env-driven so the rate can be tuned without a code change. To force-capture a
    // specific slow page (e.g. /entities/rock-room), temporarily set
    // SENTRY_TRACES_SAMPLE_RATE=1.0 in prod, run `php artisan config:cache`, reproduce,
    // then revert and re-cache. (A route-scoped traces_sampler closure can't live here:
    // closures break `php artisan config:cache`, which prod runs on deploy.)
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.05),
    // Profiling rate, relative to traces_sample_rate.
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 1.0),
);
