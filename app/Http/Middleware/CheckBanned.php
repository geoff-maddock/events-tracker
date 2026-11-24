<?php

namespace App\Http\Middleware;

use App\Models\User;
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
        $user = auth()->user();

        // a token sent to a route that has no auth middleware of its own
        if (null === $user && null !== $request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
        }

        if ($user instanceof User && $user->isSuspendedOrBanned()) {
            // The web guard by name: after a token or auth.either route the default
            // guard is Sanctum's, which cannot sign a session (or its remember
            // cookie) out.
            if ($request->hasSession()) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $user->tokens()->delete();

            // nothing later in this request should still see the refused user
            Auth::forgetGuards();

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
