<?php

namespace App\Services\Signup;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * The register form carries an encrypted "rendered at" time (#2312). A person
 * takes a few seconds to fill the form in; a bot posting straight away, or
 * posting without ever loading the form, does not.
 */
class SignupFormToken
{
    public const FIELD = 'form_token';

    // the honeypot: hidden from people, filled in by form-stuffing bots
    public const HONEYPOT = 'website';

    public static function issue(?int $renderedAt = null): string
    {
        return Crypt::encryptString((string) ($renderedAt ?? time()));
    }

    /**
     * Whether the token is genuine and was issued at least min_form_seconds,
     * and at most max_form_age_minutes, ago.
     */
    public static function isAcceptable(mixed $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        try {
            $renderedAt = Crypt::decryptString($token);
        } catch (DecryptException) {
            return false;
        }

        if (!ctype_digit($renderedAt)) {
            return false;
        }

        $elapsed = time() - (int) $renderedAt;

        return $elapsed >= (int) config('signup.min_form_seconds')
            && $elapsed <= (int) config('signup.max_form_age_minutes') * 60;
    }
}
