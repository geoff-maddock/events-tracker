<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuses throwaway-inbox domains at registration (#2312). The list is
 * resources/data/disposable_email_domains.txt, from the CC0
 * disposable-email-domains project, plus signup.extra_blocked_domains.
 * Subdomains of a listed domain are refused too.
 */
class NotDisposableEmail implements ValidationRule
{
    /** @var array<string, true>|null */
    private static ?array $domains = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::isDisposable($value)) {
            $fail('Please use a permanent email address, not a disposable one.');
        }
    }

    public static function isDisposable(string $email): bool
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }

        $domain = rtrim(strtolower(trim(substr($email, $at + 1))), '.');
        $domains = self::domains();

        // mail.example.com is refused when example.com is listed
        $parts = explode('.', $domain);
        while (count($parts) >= 2) {
            if (isset($domains[implode('.', $parts)])) {
                return true;
            }
            array_shift($parts);
        }

        return false;
    }

    /**
     * @return array<string, true>
     */
    private static function domains(): array
    {
        if (self::$domains === null) {
            $lines = file(resource_path('data/disposable_email_domains.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            self::$domains = array_fill_keys(array_map('strtolower', array_map('trim', $lines)), true);
        }

        $extra = array_map('strtolower', (array) config('signup.extra_blocked_domains', []));

        return self::$domains + array_fill_keys($extra, true);
    }
}
