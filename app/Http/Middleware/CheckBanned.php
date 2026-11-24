<?php

namespace App\Http\Middleware;

use App\Models\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBanned
{
    /**
     * A suspended or banned account is signed out and refused, however the
     * request signed in: session, API token or basic auth.
     *
     * It runs in the web and api groups and, by the Kernel's middleware
     * priority, after the middleware that signs the request in. As global
     * middleware it ran before the session had started and never saw a user.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // the default guard, or a token on a route that has no auth middleware of its own
        $user = auth()->user() ?? Auth::guard('sanctum')->user();

        if ($user && in_array((int) $user->user_status_id, [UserStatus::BANNED, UserStatus::SUSPENDED], true)) {
            // only a session can be signed out; a token or basic auth request has none
            if ($request->hasSession()) {
                if (method_exists(auth()->guard(), 'logout')) {
                    Auth::logout();
                }

                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $user->tokens()->delete();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Your Account is suspended, please contact Admin.',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Your Account is suspended, please contact Admin.');
        }

        return $next($request);
    }
}
