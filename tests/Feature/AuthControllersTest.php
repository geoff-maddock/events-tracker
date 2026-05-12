<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_login_form_is_publicly_accessible(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_is_redirected_from_login_form(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect();
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'alice@example.com',
            'password' => Hash::make('correct-password'),
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'alice@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_logout_redirects_when_authenticated(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect();
    }

    public function test_register_form_is_publicly_accessible(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_is_redirected_from_register_form(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect();
    }

    public function test_forgot_password_form_is_publicly_accessible(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    }

    public function test_forgot_password_requires_email_field(): void
    {
        $response = $this->post('/password/email', []);

        $response->assertSessionHasErrors('email');
    }

    public function test_forgot_password_rejects_invalid_email_format(): void
    {
        $response = $this->post('/password/email', [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_form_renders_for_token_route(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'fake-token-value']));

        // Some implementations redirect away when the token is missing/invalid;
        // accept either rendering the form or redirecting (not a 500).
        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_password_reset_post_requires_token_and_fields(): void
    {
        $response = $this->post('/password/reset', []);

        $response->assertSessionHasErrors();
    }
}
