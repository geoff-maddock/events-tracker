<?php

namespace Tests\Feature\Console;

use App\Mail\WeeklyUpdate;
use App\Models\Event;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifyWeeklyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function attendFuture(User $user, int $daysAhead = 3): Event
    {
        $event = Event::factory()->create([
            'start_at' => Carbon::now()->addDays($daysAhead),
        ]);

        $responseTypeId = DB::table('response_types')->where('name', 'Attending')->value('id');

        DB::table('event_responses')->insert([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => $responseTypeId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $event;
    }

    public function test_user_with_weekly_setting_and_future_event_gets_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_weekly_update' => 1,
        ]);
        $this->attendFuture($user);

        $this->artisan('notifyWeekly')->assertExitCode(0);

        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_user_with_weekly_setting_off_is_skipped(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_weekly_update' => 0,
        ]);
        $this->attendFuture($user);

        $this->artisan('notifyWeekly')->assertExitCode(0);

        Mail::assertNotSent(WeeklyUpdate::class);
    }

    public function test_user_without_profile_is_skipped(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->attendFuture($user);

        $this->artisan('notifyWeekly')->assertExitCode(0);

        Mail::assertNotSent(WeeklyUpdate::class);
    }
}
