<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Locks in the auth route wiring after consolidating the duplicate
 * Auth::routes() calls and the shadowed manual auth route block in
 * routes/web.php. If a refactor (or a laravel/ui change) drops one of
 * these names or the signed-verification hardening, this fails loudly.
 */
class AuthRouteWiringTest extends TestCase
{
    public function test_auth_route_names_are_registered(): void
    {
        foreach ([
            'login',
            'logout',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'verification.notice',
            'verification.verify',
            'verification.resend',
        ] as $name) {
            $this->assertTrue(Route::has($name), "Route [$name] is not registered");
        }
    }

    public function test_verification_verify_route_is_signed_and_throttled(): void
    {
        $route = Route::getRoutes()->getByName('verification.verify');

        $this->assertNotNull($route);
        $this->assertContains('signed:relative', $route->middleware());
        $this->assertContains('throttle:6,1', $route->middleware());
    }
}
