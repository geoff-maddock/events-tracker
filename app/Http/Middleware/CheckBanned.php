<?php

namespace App\Http\Middleware;

use App\Models\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (auth()->check() && (auth()->user()->user_status_id == UserStatus::BANNED || auth()->user()->user_status_id == UserStatus::SUSPENDED)) {
            // handles standard web logout
            Auth::logout();

            // Deletes all tokens for the user - this fixes an API issue.  May need to break this out.
            $request->user()->tokens()->delete(); 

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your Account is suspended, please contact Admin.');
        }

        return $next($request);
    }
}
