<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ThrottlesFailedBasicAuth;
use Closure;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth as Middleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Basic-auth-only routes (POST /api/tokens/create) share the failed-attempt
 * throttle with AuthenticateEither.
 */
class AuthenticateWithBasicAuth extends Middleware
{
    use ThrottlesFailedBasicAuth;

    public function handle($request, Closure $next, $guard = null, $field = null)
    {
        if ($request->getUser() !== null && $this->basicAuthLockedOut($request)) {
            return $this->tooManyFailedBasicAuthResponse($request);
        }

        try {
            return parent::handle($request, $next, $guard, $field);
        } catch (UnauthorizedHttpException $e) {
            if ($request->getUser() !== null) {
                $this->recordFailedBasicAuth($request);
            }

            throw $e;
        }
    }
}
