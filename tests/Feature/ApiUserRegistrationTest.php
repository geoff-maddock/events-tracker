<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiUserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function it_can_register_a_new_user_via_api()
    {
        Notification::fake();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'valid-captcha-token'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]);

        // Assert user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'user_status_id' => UserStatus::PENDING,
            'email_verified_at' => null
        ]);

        // Assert verification email was sent
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);

        // Assert profile was created
        $this->assertNotNull($user->profile);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password', 'g-recaptcha-response']);
    }

    /** @test */
    public function it_validates_name_minimum_length()
    {
        $userData = [
            'name' => 'ab', // Only 2 characters
            'email' => 'test@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'valid-captcha-token'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'g-recaptcha-response' => 'valid-captcha-token'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        // Create an existing user
        User::factory()->create(['email' => 'test@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'valid-captcha-token'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_password_minimum_length()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '1234567', // Only 7 characters
            'g-recaptcha-response' => 'valid-captcha-token'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_requires_captcha_verification()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
            // Missing g-recaptcha-response
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['g-recaptcha-response']);
    }

    /** @test */
    public function it_creates_user_with_pending_status()
    {
        Notification::fake();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'valid-captcha-token'
        ];

        $this->postJson('/api/register', $userData);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertEquals(UserStatus::PENDING, $user->user_status_id);
        $this->assertNull($user->email_verified_at);
    }
}