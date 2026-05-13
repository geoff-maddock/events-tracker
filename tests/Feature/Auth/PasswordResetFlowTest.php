<?php

namespace Tests\Feature\Auth;

use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_link_request_form_loads(): void
    {
        $this->get('/password/reset')->assertOk();
    }

    public function test_send_reset_link_emails_known_user_and_logs_activity(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'zz-reset@example.com',
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $this->post('/password/email', ['email' => 'zz-reset@example.com'])
            ->assertStatus(302);

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action_id' => Action::PASSWORD_RESET_REQUEST,
        ]);
    }

    public function test_send_reset_link_does_not_log_activity_for_unknown_email(): void
    {
        $this->post('/password/email', ['email' => 'nobody-zz@example.com']);

        $this->assertSame(0, Activity::where('action_id', Action::PASSWORD_RESET_REQUEST)->count());
    }

    public function test_send_reset_link_validates_email(): void
    {
        $this->post('/password/email', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_reset_form_loads_with_token(): void
    {
        $this->get('/password/reset/test-token-zz?email=foo@example.com')
            ->assertOk();
    }
}
