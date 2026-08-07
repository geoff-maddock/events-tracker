<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proactive feedback interview (issue #1998)
    |--------------------------------------------------------------------------
    |
    | Master switch for the entire feedback survey feature. When false:
    | no invitations are generated, no prompt is displayed, and every
    | feedback endpoint returns 404.
    |
    | Always read these through config(), never env() — production caches
    | the config and env() returns null outside of config files.
    |
    */

    'enabled' => env('FEEDBACK_ENABLED', false),

    /*
    | Post-event attendee campaign. Targets users who marked "Attending" on
    | an event that has since ended.
    */
    'post_event' => [
        'enabled' => env('FEEDBACK_POST_EVENT_ENABLED', true),

        // Wait this long after the event ends before asking.
        'delay_hours' => 12,

        // Don't ask about events that ended more than this many days ago.
        'lookback_days' => 14,

        // Invitations self-expire after this many days.
        'expire_days' => 30,
    ],

    /*
    | Volume controls, applied across all campaigns. Without these a heavy
    | attendee gets a prompt for every event they went to over a busy weekend.
    */
    'max_pending_per_user' => 1,

    'min_days_between_prompts' => 7,

    /*
    | Candidate rows processed per keyset chunk by feedback:generate. Lowered in
    | tests to exercise multi-chunk behaviour.
    */
    'generation_chunk_size' => 500,

];
