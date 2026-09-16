<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Every basic auth attempt runs a password hash check, so without a cap the
 * API is an unmetered password-guessing endpoint. Failed attempts are counted
 * per IP; once over the limit, basic auth is refused (even with the right
 * password) until the window expires.
 */
trait ThrottlesFailedBasicAuth
{
    public static int $maxFailedBasicAuthAttempts = 20;

    public static int $failedBasicAuthDecaySeconds = 60;

    protected function basicAuthLockedOut(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->failedBasicAuthKey($request), static::$maxFailedBasicAuthAttempts);
    }

    protected function recordFailedBasicAuth(Request $request): void
    {
        RateLimiter::hit($this->failedBasicAuthKey($request), static::$failedBasicAuthDecaySeconds);
    }

    protected function tooManyFailedBasicAuthResponse(Request $request): JsonResponse
    {
        $retryAfter = RateLimiter::availableIn($this->failedBasicAuthKey($request));

        return response()->json(
            ['message' => 'Too many failed login attempts. Try again in ' . $retryAfter . ' seconds.'],
            429,
            ['Retry-After' => $retryAfter]
        );
    }

    protected function failedBasicAuthKey(Request $request): string
    {
        return 'api-basic-auth-failed:' . $request->ip();
    }
}
