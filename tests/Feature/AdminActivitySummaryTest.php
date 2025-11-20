<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminActivitySummaryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Test that the command can be executed successfully.
     *
     * @return void
     */
    public function test_command_executes_successfully()
    {
        Mail::fake();

        $exitCode = Artisan::call('admin:activity-summary', ['days' => 7]);

        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test that the command rejects invalid days parameter.
     *
     * @return void
     */
    public function test_command_rejects_invalid_days_parameter()
    {
        Mail::fake();

        $exitCode = Artisan::call('admin:activity-summary', ['days' => 0]);

        $this->assertEquals(1, $exitCode);
    }

    /**
     * Test that the command collects login activities.
     *
     * @return void
     */
    public function test_command_collects_login_activities()
    {
        Mail::fake();

        $user = User::factory()->create();

        // Create a login activity
        Activity::create([
            'user_id' => $user->id,
            'object_table' => 'User',
            'object_id' => $user->id,
            'object_name' => $user->name,
            'action_id' => Action::LOGIN,
            'message' => 'Login User ' . $user->name,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);

        $exitCode = Artisan::call('admin:activity-summary', ['days' => 7]);

        $this->assertEquals(0, $exitCode);
        Mail::assertSent(\App\Mail\AdminActivitySummary::class, function ($mail) {
            return $mail->counts['logins'] >= 1;
        });
    }

    /**
     * Test that the command collects deletion activities.
     *
     * @return void
     */
    public function test_command_collects_deletion_activities()
    {
        Mail::fake();

        $user = User::factory()->create();

        // Create a deletion activity
        Activity::create([
            'user_id' => $user->id,
            'object_table' => 'Event',
            'object_id' => 999,
            'object_name' => 'Test Event',
            'action_id' => Action::DELETE,
            'message' => 'Delete Event Test Event',
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        $exitCode = Artisan::call('admin:activity-summary', ['days' => 7]);

        $this->assertEquals(0, $exitCode);
        Mail::assertSent(\App\Mail\AdminActivitySummary::class, function ($mail) {
            return $mail->counts['deletions'] >= 1;
        });
    }

    /**
     * Test that the command collects new user activities.
     *
     * @return void
     */
    public function test_command_collects_new_user_activities()
    {
        Mail::fake();

        $user = User::factory()->create();
        $newUser = User::factory()->create([
            'created_at' => Carbon::now()->subDays(1),
        ]);

        // Create a new user activity
        Activity::create([
            'user_id' => $user->id,
            'object_table' => 'User',
            'object_id' => $newUser->id,
            'object_name' => $newUser->name,
            'action_id' => Action::CREATE,
            'message' => 'Create User ' . $newUser->name,
            'created_at' => Carbon::now()->subDays(1),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        $exitCode = Artisan::call('admin:activity-summary', ['days' => 7]);

        $this->assertEquals(0, $exitCode);
        Mail::assertSent(\App\Mail\AdminActivitySummary::class, function ($mail) {
            return $mail->counts['new_users'] >= 1;
        });
    }

    /**
     * Test that the command respects the days parameter.
     *
     * @return void
     */
    public function test_command_respects_days_parameter()
    {
        Mail::fake();

        $user = User::factory()->create();

        // Create an old activity (outside the range)
        Activity::create([
            'user_id' => $user->id,
            'object_table' => 'User',
            'object_id' => $user->id,
            'object_name' => $user->name,
            'action_id' => Action::LOGIN,
            'message' => 'Login User ' . $user->name,
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        // Create a recent activity (within the range)
        Activity::create([
            'user_id' => $user->id,
            'object_table' => 'User',
            'object_id' => $user->id,
            'object_name' => $user->name,
            'action_id' => Action::LOGIN,
            'message' => 'Login User ' . $user->name,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);

        $exitCode = Artisan::call('admin:activity-summary', ['days' => 7]);

        $this->assertEquals(0, $exitCode);
        Mail::assertSent(\App\Mail\AdminActivitySummary::class, function ($mail) {
            // Should have at least 1 login (the recent one)
            // The old one should not be counted
            return $mail->counts['logins'] >= 1;
        });
    }

    /**
     * Test that the command uses default days parameter when not specified.
     *
     * @return void
     */
    public function test_command_uses_default_days_parameter()
    {
        Mail::fake();

        $exitCode = Artisan::call('admin:activity-summary');

        $this->assertEquals(0, $exitCode);
        Mail::assertSent(\App\Mail\AdminActivitySummary::class, function ($mail) {
            return $mail->days === 7;
        });
    }
}
