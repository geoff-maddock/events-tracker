<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function basicAuth(string $password = 'password'): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->user->email . ':' . $password),
        ];
    }

    public function test_anonymous_api_requests_are_limited_per_ip(): void
    {
        $this->getJson('/api/search?keyword=test')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '120');
    }

    public function test_basic_auth_requests_get_the_per_user_limit(): void
    {
        // proves AuthenticateEither runs before the throttle, so the limiter sees the user
        $this->get('/api/event-types', $this->basicAuth())
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '240');
    }

    public function test_token_requests_get_the_per_user_limit(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->get('/api/event-types', ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '240');
    }

    public function test_api_returns_429_once_the_limit_is_used_up(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/search?keyword=test')->assertOk();
        }

        $this->getJson('/api/search?keyword=test')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_repeated_failed_basic_auth_is_locked_out_even_with_the_right_password(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get('/api/event-types', $this->basicAuth('wrong'))->assertStatus(401);
        }

        $this->get('/api/event-types', $this->basicAuth())
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_successful_basic_auth_does_not_count_toward_lockout(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->get('/api/event-types', $this->basicAuth())->assertOk();
        }
    }

    public function test_token_create_route_shares_the_failed_basic_auth_lockout(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/api/tokens/create', ['token_name' => 'x'], $this->basicAuth('wrong'))->assertStatus(401);
        }

        $this->post('/api/tokens/create', ['token_name' => 'x'], $this->basicAuth())
            ->assertStatus(429);
    }
}
