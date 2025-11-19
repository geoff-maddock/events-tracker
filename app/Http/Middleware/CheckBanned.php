<?php

namespace App\Http\Middleware;

use App\Models\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the authenticated user
        $user = auth()->user();
        
        // Check if user exists and is banned or suspended
        if ($user && ($user->user_status_id == UserStatus::BANNED || $user->user_status_id == UserStatus::SUSPENDED)) {
            
            // Check if logout method exists on the current guard
            if (method_exists(auth()->guard(), 'logout')) {
                Auth::logout();
            }

            // Deletes all tokens for the user - this fixes an API issue
            if ($user && method_exists($user, 'tokens')) {
                // check that the user is a User model
                if ($user instanceof User)
                {
                    // Delete all tokens associated with the user
                    $user->tokens()->delete();
                }
            }

            // Only invalidate session if it exists (not for API requests)
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // For API requests, return JSON response instead of redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your Account is suspended, please contact Admin.'
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Your Account is suspended, please contact Admin.');
        }

        return $next($request);
    }
}
