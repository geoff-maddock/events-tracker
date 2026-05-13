<?php

namespace Tests\Feature\Auth;

use App\Models\Action;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ApiResetPasswordHappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config()->set('app.password_reset_secret', 'test-shared-secret');
    }

    public function test_send_reset_email_succeeds_with_known_user_and_secret(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'zz-api-reset@example.com',
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $this->postJson('/api/user/send-password-reset-email', [
            'email' => 'zz-api-reset@example.com',
            'secret' => 'test-shared-secret',
        ])->assertStatus(200);

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action_id' => Action::PASSWORD_RESET_REQUEST,
        ]);
    }

    public function test_reset_password_succeeds_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'zz-api-reset2@example.com',
            'user_status_id' => UserStatus::ACTIVE,
            'password' => Hash::make('old-password'),
        ]);

        // Generate a real reset token via the broker — the controller's
        // call to Password::broker()->reset() validates against this token.
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/user/reset-password', [
            'email' => 'zz-api-reset2@example.com',
            'password' => 'fresh-password-zz',
            'token' => $token,
            'secret' => 'test-shared-secret',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('fresh-password-zz', $user->fresh()->password));
        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action_id' => Action::PASSWORD_RESET,
        ]);
    }

    public function test_reset_password_returns_400_for_invalid_token(): void
    {
        User::factory()->create([
            'email' => 'zz-api-reset3@example.com',
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $this->postJson('/api/user/reset-password', [
            'email' => 'zz-api-reset3@example.com',
            'password' => 'new-password',
            'token' => 'this-is-not-valid',
            'secret' => 'test-shared-secret',
        ])->assertStatus(400);
    }
}
