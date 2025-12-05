<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EnsureEmailIsVerifiedMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function middleware_handles_null_user_gracefully_for_web_request()
    {
        // Create a request
        $request = Request::create('/test', 'GET');
        
        // Ensure no user is authenticated
        Auth::logout();
        
        // Create the middleware
        $middleware = new EnsureEmailIsVerified();
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next);
        
        // Assert that next was NOT called (user was redirected to login)
        $this->assertFalse($nextCalled);
        
        // Assert redirect to login
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function middleware_handles_null_user_gracefully_for_api_request()
    {
        // Create an API request (expects JSON)
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        // Ensure no user is authenticated
        Auth::logout();
        
        // Create the middleware
        $middleware = new EnsureEmailIsVerified();
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware - should throw an exception caught as 401
        try {
            $response = $middleware->handle($request, $next);
            // If we get here, next was not called
            $this->assertFalse($nextCalled);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Expected behavior: 401 Unauthenticated
            $this->assertEquals(401, $e->getStatusCode());
            $this->assertFalse($nextCalled);
        }
    }

    /** @test */
    public function middleware_allows_verified_user()
    {
        // Create a verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'user_status_id' => UserStatus::ACTIVE,
        ]);
        
        // Authenticate the user
        $this->actingAs($user);
        
        // Create the middleware
        $middleware = new EnsureEmailIsVerified();
        
        // Create a request with user set
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next);
        
        // Assert that next was called (user was allowed through)
        $this->assertTrue($nextCalled);
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function middleware_blocks_unverified_user_for_web_request()
    {
        // Create an unverified user
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);
        
        // Authenticate the user
        $this->actingAs($user);
        
        // Create the middleware
        $middleware = new EnsureEmailIsVerified();
        
        // Create a request with user set
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next);
        
        // Assert that next was NOT called (user was redirected)
        $this->assertFalse($nextCalled);
        
        // Assert redirect (to verification notice)
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function middleware_blocks_unverified_user_for_api_request()
    {
        // Create an unverified user
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);
        
        // Authenticate the user
        $this->actingAs($user);
        
        // Create the middleware
        $middleware = new EnsureEmailIsVerified();
        
        // Create an API request with user set
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware - should throw 403
        try {
            $response = $middleware->handle($request, $next);
            $this->assertFalse($nextCalled);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Expected behavior: 403 Email not verified
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertFalse($nextCalled);
        }
    }
}
