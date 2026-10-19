<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as BaseEnsureEmailIsVerified;
use Illuminate\Http\Request;

/**
 * Laravel's verified middleware, except API callers always get the JSON 403.
 * The base class only does that when the request sends Accept: application/json
 * and otherwise redirects to the web verification page, which means nothing to
 * a script calling the API with basic auth (#2314).
 */
class EnsureEmailIsVerified extends BaseEnsureEmailIsVerified
{
    /**
     * @param  Request  $request
     * @param  string|null  $redirectToRoute
     * @return mixed
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        $user = $request->user();

        if ($request->is('api/*') && (!$user || !$user->hasVerifiedEmail())) {
            return response()->json(['message' => 'Your email address is not verified.'], 403);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
