<?php

return array(
    'dsn' => env('SENTRY_DSN'),

    // capture release as git sha
    // 'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),

    // Capture bindings on SQL queries
    'breadcrumbs.sql_bindings' => true,

    // Capture default user context
   //  'user_context' => true,
    'traces_sample_rate' => 0.05,
    // Set a sampling rate for profiling - this is relative to traces_sample_rate
    'profiles_sample_rate' => 1.0,
);
