<?php

return [
    // monitor:check reports when a queued job has been waiting this long.
    // The one worker can spend 20 minutes on an Instagram job, so keep it above that.
    'queue_max_wait_minutes' => (int) env('MONITOR_QUEUE_MAX_WAIT_MINUTES', 45),

    // monitor:check reports when the Facebook/Instagram token expires within this many days
    'token_warn_days' => (int) env('MONITOR_TOKEN_WARN_DAYS', 14),

    // how long before the same problem is reported again
    'repeat_hours' => (int) env('MONITOR_REPEAT_HOURS', 6),
];
