<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Models\User;


abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected ?User $user;

    public function __construct()
    {

        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            View::share('signedIn', Auth::check());
            View::share('user', $this->user);
            return $next($request);
        });


    }

    /**
     * Guard an action so only admins may proceed. Returns a 403 JSON
     * response to short-circuit the action, or null when the current user
     * is an admin/super_admin. Usage:
     *   if ($denied = $this->requireAdmin()) { return $denied; }
     */
    protected function requireAdmin(): ?JsonResponse
    {
        if (!$this->user || !$this->user->isAdmin()) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        return null;
    }

    /**
     * Resolve the `redirect` request param (a route name) used by the
     * reset/rpp-reset actions, falling back to $default when it is missing
     * or does not name a real route.
     */
    protected function resolveRedirectRoute(Request $request, string $default): string
    {
        $redirect = $request->get('redirect');

        return is_string($redirect) && Route::has($redirect) ? $redirect : $default;
    }
}
