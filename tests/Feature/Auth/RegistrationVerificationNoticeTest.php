<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegistrationVerificationNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        // The registration form is captcha-gated; make the rule a no-op here.
        Validator::extend('captcha', fn () => true);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Notice Tester',
            'email' => 'notice-'.uniqid().'@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'g-recaptcha-response' => 'valid-captcha-token',
        ], $overrides);
    }

    public function test_registration_flashes_the_verification_notice(): void
    {
        Notification::fake();

        $payload = $this->registrationPayload();

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/');
        $response->assertSessionHas('show_verification_notice', $payload['email']);
    }

    public function test_landing_page_renders_the_activation_modal_after_registering(): void
    {
        // Pin the status: the factory randomises it, and CheckBanned bounces
        // banned/suspended users to login before the page ever renders.
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['show_verification_notice' => $user->email])
            ->get('/');

        $response->assertOk();
        $response->assertSee('Check your email');
        $response->assertSee('activation link', false);
        $response->assertSee($user->email);
    }

    public function test_activation_modal_is_not_shown_without_the_flash(): void
    {
        // Pin the status: the factory randomises it, and CheckBanned bounces
        // banned/suspended users to login before the page ever renders.
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertDontSee('verify-email-title');
    }
}
