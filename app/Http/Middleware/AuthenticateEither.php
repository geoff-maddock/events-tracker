<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ThrottlesFailedBasicAuth;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Implements AuthenticatesRequests so the kernel's middleware priority sorts it
 * ahead of ThrottleRequests, letting the 'api' rate limiter key by user.
 */
class AuthenticateEither implements AuthenticatesRequests
{
    use ThrottlesFailedBasicAuth;

    public function handle($request, Closure $next)
    {
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // only count attempts that actually supplied basic credentials
        if ($request->getUser() !== null) {
            if ($this->basicAuthLockedOut($request)) {
                return $this->tooManyFailedBasicAuthResponse($request);
            }

            try {
                if ($this->attemptBasic($request)) {
                    return $next($request);
                }
            } catch (UnauthorizedHttpException $e) {
                // onceBasic() throws on bad credentials rather than returning a response
                $this->recordFailedBasicAuth($request);

                throw $e;
            }

            $this->recordFailedBasicAuth($request);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    protected function attemptBasic($request)
    {
        return Auth::onceBasic() === null;
    }
}
