<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthenticateEither
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('sanctum')->check() || Auth::guard('web')->basic() === null) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}