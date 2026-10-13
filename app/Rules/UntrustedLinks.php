<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Caps the links an account that isn't trusted yet can put in its profile
 * text: bio, display name, alias and location (#2312). Spam accounts that get
 * past signup use these as a billboard; a real new member can add links once
 * the account is established.
 */
class UntrustedLinks implements ValidationRule
{
    public function __construct(private readonly bool $allowLinks, private readonly int $maxLinks = 0)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->allowLinks || !is_string($value)) {
            return;
        }

        if (self::countLinks($value) > $this->maxLinks) {
            $fail('Links aren\'t allowed in your :attribute until your account is established. Please remove them for now.');
        }
    }

    /**
     * Counts URLs, www. addresses and bare domains on common TLDs.
     */
    public static function countLinks(string $text): int
    {
        return (int) preg_match_all(
            '~https?://\S+|\bwww\.\S+|\b[a-z0-9][a-z0-9-]*\.(?:com|net|org|info|biz|io|co|me|ru|cn|xyz|top|shop|site|online|store|club|live|pro|link|click)\b~i',
            $text
        );
    }
}
