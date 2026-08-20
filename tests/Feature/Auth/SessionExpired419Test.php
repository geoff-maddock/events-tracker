<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpired419Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        // VerifyCsrfToken is a no-op under runningUnitTests(), so throw the
        // exception it would raise and assert on the handler's rendering.
        Route::post('test-419', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        })->middleware('web');
    }

    public function test_token_mismatch_redirects_back_with_message_instead_of_419(): void
    {
        $response = $this->from('/login')->post('test-419', [
            'email' => 'someone@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Your session expired. Please try again.');
        $response->assertSessionHasInput('email', 'someone@example.com');
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_token_mismatch_falls_back_to_login_without_referer(): void
    {
        $this->post('test-419')->assertRedirect(route('login'));
    }

    public function test_json_requests_still_get_a_419(): void
    {
        $this->postJson('test-419')->assertStatus(419);
    }

    public function test_csrf_token_endpoint_returns_a_fresh_token(): void
    {
        $response = $this->get('/csrf-token');

        $response->assertOk();
        $response->assertJsonStructure(['token']);
        $this->assertNotEmpty($response->json('token'));
    }
}
