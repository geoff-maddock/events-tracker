<?php

namespace Tests\Feature\Console;

use App\Mail\AdminActivitySummary as AdminActivitySummaryMail;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminActivitySummaryCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        config()->set('app.admin', 'admin@example.com');
    }

    private function logActivity(int $actionId, string $objectTable, ?User $user = null, ?Carbon $at = null): Activity
    {
        $user ??= User::factory()->create();

        $activity = new Activity();
        $activity->forceFill([
            'user_id' => $user->id,
            'object_id' => 1,
            'object_table' => $objectTable,
            'object_name' => 'test',
            'action_id' => $actionId,
            'created_at' => $at ?? Carbon::now()->subDay(),
            'updated_at' => $at ?? Carbon::now()->subDay(),
        ])->save();

        return $activity;
    }

    public function test_command_sends_summary_email_to_admin(): void
    {
        Mail::fake();

        $this->logActivity(Action::LOGIN, 'User');
        $this->logActivity(Action::CREATE, 'Event');
        $this->logActivity(Action::DELETE, 'Entity');

        $this->artisan('admin:activity-summary')->assertExitCode(0);

        Mail::assertSent(AdminActivitySummaryMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    public function test_command_respects_days_argument(): void
    {
        Mail::fake();

        // Within range
        $this->logActivity(Action::LOGIN, 'User', null, Carbon::now()->subDays(2));
        // Outside range
        $this->logActivity(Action::LOGIN, 'User', null, Carbon::now()->subDays(20));

        $this->artisan('admin:activity-summary', ['days' => 3])->assertExitCode(0);

        Mail::assertSent(AdminActivitySummaryMail::class);
    }

    public function test_command_rejects_zero_days(): void
    {
        Mail::fake();

        $this->artisan('admin:activity-summary', ['days' => 0])->assertExitCode(1);

        Mail::assertNothingSent();
    }
}
