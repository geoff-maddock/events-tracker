<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Why a guest is being asked to sign in, by the route they tried.
     *
     * @var array<string, string>
     */
    private const SIGN_IN_MESSAGES = [
        'events.create' => 'Sign in to add an event.',
        'series.create' => 'Sign in to add a series.',
        'entities.create' => 'Sign in to add an entity.',
    ];

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // say why they're signing in; the login returns them to the form (#2108)
        if ($message = self::SIGN_IN_MESSAGES[$request->route()?->getName()] ?? null) {
            $request->session()->flash('sign_in_reason', $message);
        }

        return route('login');
    }
}
