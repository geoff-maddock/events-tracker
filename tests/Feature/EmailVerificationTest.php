<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_email_verification_does_not_require_authentication(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false
        );

        $this->assertGuest();

        $response = $this->get($verificationUrl);

        $response->assertStatus(302);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_requires_valid_signature(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        // Unsigned URL — knows the id+hash but lacks the HMAC signature.
        $unsignedUrl = route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->get($unsignedUrl);

        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_rejects_tampered_signature(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false
        );

        $tamperedUrl = $verificationUrl.'x';

        $response = $this->get($tamperedUrl);

        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_api_email_verification_requires_valid_signature(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $unsignedUrl = route('api.verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->getJson($unsignedUrl);

        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_api_email_verification_succeeds_with_valid_signature(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200);
        $response->assertJson(['verified' => true]);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_resend_requires_authentication(): void
    {
        User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $response = $this->post(route('verification.resend'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('verification.resend'));

        $response->assertSessionHas('resent', true);
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
