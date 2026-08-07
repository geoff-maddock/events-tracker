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
    | Volume controls, applied across all campaigns.
    |
    | max_pending_per_user is a queue depth, not a display limit — the modal
    | always shows one invitation at a time, freshest event first. It is above 1
    | because the lookback window governs *creation* while an invitation lives
    | for expire_days once it exists; with a depth of 1 a user who attended
    | several events in a week would see the extras age out of the window
    | before they were ever asked.
    */
    'max_pending_per_user' => 3,

    /*
    | Cooldown after a user *dismisses* a prompt. Completing one does not start
    | a cooldown — someone who just answered is engaged, and can work through
    | their queue as fast as they like.
    */
    'min_days_between_prompts' => 7,

];
