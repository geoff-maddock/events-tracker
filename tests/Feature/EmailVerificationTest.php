<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // /** @test */
    // public function email_verification_does_not_require_authentication()
    // {
    //     // Create an unverified user
    //     $user = User::factory()->create([
    //         'email_verified_at' => null,
    //         'user_status_id' => UserStatus::PENDING
    //     ]);

    //     // Generate a signed verification URL
    //     $verificationUrl = URL::temporarySignedRoute(
    //         'verification.verify',
    //         now()->addMinutes(60),
    //         ['id' => $user->id, 'hash' => sha1($user->email)]
    //     );

    //     // Ensure we're not authenticated
    //     $this->assertGuest();

    //     // Make request to verification URL without being logged in
    //     $response = $this->get($verificationUrl);

    //     // Should not return 403 Unauthorized - this was the bug
    //     $response->assertStatus(302); // Should redirect after successful verification
        
    //     // User should now be verified
    //     $this->assertTrue($user->fresh()->hasVerifiedEmail());
    // }

    /** @test */
    public function email_verification_requires_valid_signature()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING
        ]);

        // Create an invalid/unsigned URL
        $invalidUrl = route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]);

        $response = $this->get($invalidUrl);

        // Should return 403 for invalid signature
        $response->assertStatus(403);
        
        // User should still not be verified
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /** @test */
    public function email_verification_resend_requires_authentication()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING
        ]);

        // Try to resend verification email without being logged in
        $response = $this->post(route('verification.resend'));

        // Should require authentication
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_resend_verification_email()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING
        ]);

        // Login as the user
        $this->actingAs($user);

        // Try to resend verification email
        $response = $this->post(route('verification.resend'));

        // Should be successful
        $response->assertSessionHas('resent', true);
    }
}