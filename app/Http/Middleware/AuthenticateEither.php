<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthenticateEither
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        if ($this->attemptBasic($request)) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    protected function attemptBasic($request)
    {
        return Auth::onceBasic() === null;
    }
}