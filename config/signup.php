<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Spam signup defenses (issue #2312)
    |--------------------------------------------------------------------------
    |
    | Captcha and email verification stop scripts, but not a person (or a
    | captcha farm) with a working inbox. These settings tune the checks that
    | sit on top: invisible form checks at signup, what an account may show
    | before it is trusted, and the risk score that holds a signup for review.
    |
    | Always read these through config(), never env().
    |
    */

    // A web signup posted sooner than this after the form loaded is a bot.
    'min_form_seconds' => 3,

    // A form token older than this is refused (the form is simply reloaded).
    'max_form_age_minutes' => 24 * 60,

    // Registrations allowed per IP per hour, web and API combined.
    'registrations_per_hour' => 10,

    // Domains to refuse beyond resources/data/disposable_email_domains.txt.
    'extra_blocked_domains' => [],

    /*
    | Trust. An account shows its bio and social handles to others only once
    | it is active and verified, and either an admin approved it or it is at
    | least this many days old and has done something on the site.
    */
    'trust_after_days' => 7,

    // Bio length, and links allowed in the bio of an untrusted account.
    'bio_max_length' => 2000,
    'untrusted_bio_max_links' => 0,

    /*
    | Risk score. A signup scoring at or above the threshold stays Pending
    | after it verifies and waits in the admin signup review list.
    */
    'risk_threshold' => 3,

    // Email TLDs that are rare for real members here.
    'suspicious_tlds' => ['ru', 'cn', 'xyz', 'top', 'click', 'buzz', 'icu', 'cyou', 'shop', 'site', 'online', 'store', 'loan', 'work'],

    // Whole words or phrases. Avoid words with an ordinary meaning on a music
    // site ("slots" as in DJ slots, a band called Telegram); use a phrase instead.
    'spam_keywords' => [
        'casino', 'viagra', 'cialis', 'crypto trading', 'crypto investment', 'bitcoin', 'forex', 'payday',
        'escort', 'porn', 'sports betting', 'online slots', 'seo service', 'backlinks', 'essay writing',
        'buy cheap', 'click here', 'onlyfans', 'replica watches', 'weight loss',
    ],

    // Registrations from one IP within a day before the next one scores as a burst.
    'ip_burst_threshold' => 3,

    // Admin review list: how far back to look by default.
    'review_days' => 30,

    /*
    | StopForumSpam lookup at registration. Off by default: it sends an MD5
    | hash of the email address and the signup IP to stopforumspam.org, so
    | turn it on only once the privacy page says so. Fails open.
    */
    'stopforumspam' => [
        'enabled' => env('STOPFORUMSPAM_ENABLED', false),
        'timeout' => 3,
        // a listed IP can be shared (VPN, campus), so it needs more confidence than an email
        'ip_min_confidence' => 50,
    ],
];
