<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_users_unverified_for_more_than_seven_days_are_deleted(): void
    {
        $stale = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $this->artisan('userCleanup')->assertExitCode(0);

        $this->assertNull(User::find($stale->id));
    }

    public function test_recently_created_unverified_users_are_left_alone(): void
    {
        $fresh = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
            'created_at' => Carbon::now()->subDay(),
        ]);

        $this->artisan('userCleanup')->assertExitCode(0);

        $this->assertNotNull(User::find($fresh->id));
    }

    public function test_verified_users_are_never_deleted(): void
    {
        $verified = User::factory()->create([
            'email_verified_at' => Carbon::now()->subYear(),
            'user_status_id' => UserStatus::ACTIVE,
            'created_at' => Carbon::now()->subYears(2),
        ]);

        $this->artisan('userCleanup')->assertExitCode(0);

        $this->assertNotNull(User::find($verified->id));
    }

    public function test_users_unverified_between_three_and_seven_days_get_reminder(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::PENDING,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $this->artisan('userCleanup')->assertExitCode(0);

        // Still present (not past 7-day cutoff) and got a verification email.
        $this->assertNotNull(User::find($user->id));
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
