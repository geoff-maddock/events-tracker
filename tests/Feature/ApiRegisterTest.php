<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApiRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function testRegisterWithoutFrontendUrl()
    {
        // Disable captcha for testing
        Config::set('captcha.secret', 'test-secret');
        Config::set('captcha.sitekey', 'test-sitekey');
        
        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'test-captcha',
        ];

        // Mock captcha validation
        $this->mock(\Anhskohbo\NoCaptcha\NoCaptcha::class)
            ->shouldReceive('verifyResponse')
            ->andReturn(true);

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
            'name' => 'Test User',
        ]);
    }

    public function testRegisterWithFrontendUrl()
    {
        // Disable captcha for testing
        Config::set('captcha.secret', 'test-secret');
        Config::set('captcha.sitekey', 'test-sitekey');
        
        $userData = [
            'name' => 'Test User With Frontend',
            'email' => 'testfrontend@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'test-captcha',
            'frontend-url' => 'https://frontend.example.com',
        ];

        // Mock captcha validation
        $this->mock(\Anhskohbo\NoCaptcha\NoCaptcha::class)
            ->shouldReceive('verifyResponse')
            ->andReturn(true);

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'testfrontend@example.com',
            'name' => 'Test User With Frontend',
        ]);
    }

    public function testRegisterWithInvalidFrontendUrl()
    {
        // Disable captcha for testing
        Config::set('captcha.secret', 'test-secret');
        Config::set('captcha.sitekey', 'test-sitekey');
        
        $userData = [
            'name' => 'Test User',
            'email' => 'testinvalid@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'test-captcha',
            'frontend-url' => 'not-a-valid-url',
        ];

        // Mock captcha validation
        $this->mock(\Anhskohbo\NoCaptcha\NoCaptcha::class)
            ->shouldReceive('verifyResponse')
            ->andReturn(true);

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frontend-url']);
    }

    public function testRegisterRequiresName()
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'g-recaptcha-response' => 'test-captcha',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function testRegisterRequiresEmail()
    {
        $userData = [
            'name' => 'Test User',
            'password' => 'password123',
            'g-recaptcha-response' => 'test-captcha',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function testRegisterRequiresPassword()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'g-recaptcha-response' => 'test-captcha',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
