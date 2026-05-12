<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_send_reset_email_requires_email_field(): void
    {
        $response = $this->postJson('/api/user/send-password-reset-email', [
            'secret' => 'whatever',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_reset_email_requires_secret_field(): void
    {
        $response = $this->postJson('/api/user/send-password-reset-email', [
            'email' => 'alice@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_reset_email_rejects_invalid_secret(): void
    {
        User::factory()->create([
            'email' => 'alice@example.com',
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $response = $this->postJson('/api/user/send-password-reset-email', [
            'email' => 'alice@example.com',
            'secret' => 'definitely-not-the-real-secret-'.uniqid(),
        ]);

        $response->assertStatus(401);
    }

    public function test_send_reset_email_rejects_malformed_email(): void
    {
        $response = $this->postJson('/api/user/send-password-reset-email', [
            'email' => 'not-an-email',
            'secret' => 'anything',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_requires_all_fields(): void
    {
        $response = $this->postJson('/api/user/reset-password', []);

        $response->assertStatus(422);
    }

    public function test_reset_password_rejects_short_password(): void
    {
        $response = $this->postJson('/api/user/reset-password', [
            'email' => 'alice@example.com',
            'password' => 'short',
            'token' => 'sometoken',
            'secret' => 'anything',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_rejects_invalid_secret(): void
    {
        $response = $this->postJson('/api/user/reset-password', [
            'email' => 'alice@example.com',
            'password' => 'long-enough-password',
            'token' => 'sometoken',
            'secret' => 'wrong-secret-'.uniqid(),
        ]);

        $response->assertStatus(401);
    }
}
