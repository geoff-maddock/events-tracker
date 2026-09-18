<?php

namespace App\Http\Controllers;

use App\Events\EventUpdated;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Small system/utility endpoints.
 *
 * These actions were previously defined as Closure-based routes in
 * routes/web.php. Closure routes cannot be serialized by
 * `php artisan route:cache`, which throws a LogicException the moment it
 * encounters one. When the deploy pipeline runs `route:cache` (see
 * docs/deployment_notes.md) it therefore fails, leaving the production
 * route cache stale or missing — so newly added named routes resolve as
 * "Route [...] not defined". Housing these actions in a controller keeps
 * the whole route table cacheable.
 */
class SystemController extends Controller
{
    /**
     * Return a CSRF token bound to the caller's (possibly brand-new) session
     * so a stale page can refresh its token without a full reload (issue #2089).
     */
    public function csrfToken(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    /**
     * Lightweight sanctum-authenticated probe endpoint.
     *
     * @return array<string, string>
     */
    public function tokensTest(): array
    {
        return ['data' => 'has event check'];
    }

    /**
     * Log in as another user (admin only).
     */
    public function impersonate(User $user): RedirectResponse
    {
        Auth::login($user);

        return redirect('/');
    }

    /**
     * Dispatch an EventUpdated event (diagnostic).
     */
    public function dispatchEvent(): string
    {
        EventUpdated::dispatch();

        return 'test';
    }

    /**
     * Dispatch an EventUpdated event for a fresh Event instance (diagnostic).
     */
    public function update(): void
    {
        EventUpdated::dispatch(new Event());
    }
}
