<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedAndVerified
{
    /**
     * Handle an incoming request.
     *
     * Ensures user is authenticated first before checking email verification.
     * Redirects to login if not authenticated, or to email verification if not verified.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // First check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Then check if email is verified
        if (!$request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
