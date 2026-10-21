<?php

namespace Tests\Feature;

use App\Mail\LoginFailure;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Repeated failed logins warn the account owner (and bcc the admin) once per
 * window, not once per attempt.
 */
class LoginFailureMailTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Mail::fake();
    }

    public function test_repeated_failed_api_basic_auth_attempts_send_one_warning(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email' => 'target@example.test']);
        $header = ['Accept' => 'application/json', 'Authorization' => 'Basic '.base64_encode('target@example.test:wrong-password')];

        for ($i = 0; $i < 12; $i++) {
            $this->app['auth']->forgetGuards();
            $this->getJson('/api/events', $header)->assertUnauthorized();
        }

        Mail::assertSent(LoginFailure::class, 1);
        Mail::assertSent(LoginFailure::class, fn (LoginFailure $mail) => $mail->hasTo($user->email));
    }

    public function test_the_warning_is_per_account(): void
    {
        $first = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $second = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        foreach ([$first, $second] as $user) {
            for ($i = 0; $i < 8; $i++) {
                event(new Failed('web', $user, ['email' => $user->email, 'password' => 'wrong']));
            }
        }

        Mail::assertSent(LoginFailure::class, 2);
    }

    public function test_no_warning_below_the_limit(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        for ($i = 0; $i < 5; $i++) {
            event(new Failed('web', $user, ['email' => $user->email, 'password' => 'wrong']));
        }

        Mail::assertNotSent(LoginFailure::class);
    }
}
