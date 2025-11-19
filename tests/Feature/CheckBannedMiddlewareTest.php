<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckBanned;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CheckBannedMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function middleware_handles_null_user_gracefully()
    {
        // Create a request
        $request = Request::create('/test', 'GET');
        
        // Ensure no user is authenticated
        Auth::logout();
        
        // Create the middleware
        $middleware = new CheckBanned();
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next);
        
        // Assert that next was called (middleware passed through)
        $this->assertTrue($nextCalled);
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function middleware_blocks_banned_users()
    {
        // Create a banned user
        $user = User::factory()->create([
            'user_status_id' => UserStatus::BANNED,
        ]);
        
        // Authenticate the user
        $this->actingAs($user);
        
        // Create the middleware
        $middleware = new CheckBanned();
        
        // Create a request
        $request = Request::create('/test', 'GET');
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next);
        
        // Assert that next was NOT called (user was blocked)
        $this->assertFalse($nextCalled);
        
        // Assert redirect to login
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function middleware_blocks_suspended_users()
    {
        // Create a suspended user
        $user = User::factory()->create([
            'user_status_id' => UserStatus::SUSPENDED,
        ]);
        
        // Authenticate the user
        $this->actingAs($user);
        
        // Create the middleware
        $middleware = new CheckBanned();
        
        // Create a request
        $request = Request::create('/test', 'GET');
        
        // Create a mock next closure
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next);
        
        // Assert that next was NOT called (user was blocked)
        $this->assertFalse($nextCalled);
        
        // Assert redirect to login
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function middleware_allows_active_users()
    {
        // Create an active user
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
        ]);
        
        // Authenticate the user
        $this->actingAs($user);
        
        // Create the middleware
        $middleware = new CheckBanned();
        
        // Create a request
        $request = Request::create('/test', 'GET');
        
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
    public function middleware_returns_json_for_api_requests_when_banned()
    {
        // Create a banned user
        $user = User::factory()->create([
            'user_status_id' => UserStatus::BANNED,
        ]);
        
        // Authenticate the user
        $this->actingAs($user);
        
        // Create the middleware
        $middleware = new CheckBanned();
        
        // Create an API request (expects JSON)
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        // Create a mock next closure
        $next = function ($req) {
            return response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next);
        
        // Assert JSON response
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('suspended', strtolower($response->getContent()));
    }
}
