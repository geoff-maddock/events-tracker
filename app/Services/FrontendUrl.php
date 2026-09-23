<?php

namespace App\Services;

/**
 * Resolves the base URL used in emailed auth links (password reset, email verification).
 *
 * Clients may pass a `frontend-url` so links open their own frontend, but the value is
 * attacker-controllable, so it is only honoured when its origin is allowlisted.
 */
class FrontendUrl
{
    public static function default(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    /**
     * Return the candidate (without trailing slash) if its origin is allowed, otherwise the default.
     */
    public static function resolve(?string $candidate): string
    {
        if (is_string($candidate) && $candidate !== '' && self::isAllowed($candidate)) {
            return rtrim($candidate, '/');
        }

        return self::default();
    }

    public static function isAllowed(string $url): bool
    {
        $origin = self::origin($url);

        if ($origin === null) {
            return false;
        }

        $allowed = array_merge(
            [config('app.frontend_url'), config('app.url')],
            (array) config('app.allowed_frontend_urls', [])
        );

        foreach ($allowed as $candidate) {
            if (is_string($candidate) && self::origin($candidate) === $origin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalised scheme://host[:port] for an http(s) URL, or null if it is not one.
     */
    private static function origin(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (!is_array($parts) || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        // userinfo (user@host) is never legitimate here and is a common way to disguise a host
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $origin = strtolower($parts['scheme']).'://'.strtolower($parts['host']);

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }
}
