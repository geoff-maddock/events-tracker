<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Rules\SignupFormSubmission;
use App\Services\Signup\SignupFormToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * The invisible checks in front of registration (#2312): a honeypot, a
 * minimum time to fill the form, disposable email domains, and a per-IP cap.
 */
class SignupBotChecksTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Notification::fake();

        // captcha-gated; make the rule a no-op here
        Validator::extend('captcha', fn () => true);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bot Check Tester',
            'email' => 'bot-check-'.uniqid().'@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'g-recaptcha-response' => 'valid-captcha-token',
            SignupFormToken::FIELD => SignupFormToken::issue(time() - 10),
        ], $overrides);
    }

    public function test_the_form_renders_the_token_and_the_honeypot(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="'.SignupFormToken::FIELD.'"', false)
            ->assertSee('name="'.SignupFormToken::HONEYPOT.'"', false);
    }

    public function test_a_person_filling_the_form_registers(): void
    {
        $payload = $this->payload();

        $this->post('/register', $payload)->assertRedirect('/');

        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    public function test_a_filled_honeypot_is_refused_without_saying_why(): void
    {
        $payload = $this->payload([SignupFormToken::HONEYPOT => 'https://spam.example']);

        $this->post('/register', $payload)
            ->assertSessionHasErrors([SignupFormToken::HONEYPOT => SignupFormSubmission::MESSAGE]);

        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_a_form_posted_too_quickly_is_refused(): void
    {
        $payload = $this->payload([SignupFormToken::FIELD => SignupFormToken::issue(time())]);

        $this->post('/register', $payload)
            ->assertSessionHasErrors([SignupFormToken::FIELD => SignupFormSubmission::MESSAGE]);

        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_a_post_without_the_form_or_with_a_forged_token_is_refused(): void
    {
        $missing = $this->payload();
        unset($missing[SignupFormToken::FIELD]);
        $this->post('/register', $missing)->assertSessionHasErrors(SignupFormToken::FIELD);

        $this->post('/register', $this->payload([SignupFormToken::FIELD => (string) (time() - 60)]))
            ->assertSessionHasErrors(SignupFormToken::FIELD);
    }

    public function test_a_stale_token_is_refused(): void
    {
        $stale = time() - (int) config('signup.max_form_age_minutes') * 60 - 60;

        $this->post('/register', $this->payload([SignupFormToken::FIELD => SignupFormToken::issue($stale)]))
            ->assertSessionHasErrors(SignupFormToken::FIELD);
    }

    public function test_disposable_email_domains_are_refused_on_web_and_api(): void
    {
        $this->post('/register', $this->payload(['email' => 'someone@mailinator.com']))
            ->assertSessionHasErrors('email');

        // subdomains of a listed domain too
        $this->post('/register', $this->payload(['email' => 'someone@inbox.mailinator.com']))
            ->assertSessionHasErrors('email');

        $this->postJson('/api/register', [
            'name' => 'Api Tester',
            'email' => 'someone@mailinator.com',
            'password' => 'password1234',
            'g-recaptcha-response' => 'valid-captcha-token',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_the_api_refuses_a_filled_honeypot(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Api Tester',
            'email' => 'api-honeypot@example.com',
            'password' => 'password1234',
            'g-recaptcha-response' => 'valid-captcha-token',
            SignupFormToken::HONEYPOT => 'filled',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'api-honeypot@example.com']);
    }

    public function test_registrations_are_capped_per_ip_across_web_and_api(): void
    {
        config(['signup.registrations_per_hour' => 3]);

        // failed attempts count too, and the web and API share one budget
        $this->post('/register', []);
        $this->postJson('/api/register', [])->assertStatus(422);
        $this->postJson('/api/register', [])->assertStatus(422);

        $this->postJson('/api/register', [])->assertStatus(429);
        $this->post('/register', $this->payload())->assertStatus(429);
    }

    public function test_a_web_registration_is_logged_with_its_ip(): void
    {
        $payload = $this->payload();
        $this->post('/register', $payload);

        $user = User::where('email', $payload['email'])->sole();
        $this->assertDatabaseHas('activities', [
            'object_table' => 'User',
            'object_id' => $user->id,
            'action_id' => 1,
            'ip_address' => '127.0.0.1',
        ]);
    }
}
