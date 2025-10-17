<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApiEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Re-enable exception handling for these API tests
        $this->withExceptionHandling();
    }

    /** @test */
    public function api_email_verification_works_with_valid_link()
    {
        Event::fake();

        // Create an unverified user
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING
        ]);

        // Generate a signed verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Make request to verification URL
        $response = $this->getJson($verificationUrl);

        // Should return 200 with success message
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Email verified successfully.',
                    'verified' => true
                ]);
        
        // User should now be verified
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // Verified event should have been fired
        Event::assertDispatched(Verified::class);
    }

    /** @test */
    public function api_email_verification_returns_error_for_invalid_hash()
    {
        // Create an unverified user
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING
        ]);

        // Generate a signed URL with wrong hash
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->getJson($verificationUrl);

        // Should return 400 error
        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Invalid verification link.'
                ]);
        
        // User should still not be verified
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /** @test */
    public function api_email_verification_returns_success_if_already_verified()
    {
        // Create an already verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'user_status_id' => UserStatus::ACTIVE
        ]);

        // Generate a signed verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($verificationUrl);

        // Should return 200 with already verified message
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Email already verified.'
                ]);
    }


    /** @test */
    public function api_email_verification_returns_404_for_non_existent_user()
    {
        // Generate a signed URL for non-existent user
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            ['id' => 99999, 'hash' => sha1('nonexistent@example.com')]
        );

        $response = $this->getJson($verificationUrl);

        // Should return 404
        $response->assertStatus(404);
    }

    /** @test */
    public function api_email_verification_is_throttled()
    {
        // Create an unverified user
        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING
        ]);

        // Generate a signed verification URL with wrong hash to test throttling
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'wrong-hash']
        );

        // Make 7 requests (throttle is set to 6 per minute)
        for ($i = 0; $i < 7; $i++) {
            $response = $this->getJson($verificationUrl);
            
            if ($i < 6) {
                // First 6 requests should go through (even if they fail)
                $this->assertContains($response->getStatusCode(), [400, 403]);
            } else {
                // 7th request should be throttled
                $response->assertStatus(429);
            }
        }
    }
}
