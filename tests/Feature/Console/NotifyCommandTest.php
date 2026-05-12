<?php

namespace Tests\Feature\Console;

use App\Mail\DailyReminder;
use App\Models\Event;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function attendToday(User $user): Event
    {
        $event = Event::factory()->create([
            'start_at' => Carbon::now()->setTime(20, 0),
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

    public function test_user_with_daily_setting_and_event_today_receives_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_daily_update' => 1,
        ]);
        $this->attendToday($user);

        $this->artisan('notify')->assertExitCode(0);

        Mail::assertSent(DailyReminder::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_user_without_profile_is_skipped(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        // No profile created.
        $this->attendToday($user);

        $this->artisan('notify')->assertExitCode(0);

        Mail::assertNotSent(DailyReminder::class);
    }

    public function test_user_with_daily_setting_off_is_skipped(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_daily_update' => 0,
        ]);
        $this->attendToday($user);

        $this->artisan('notify')->assertExitCode(0);

        Mail::assertNotSent(DailyReminder::class);
    }

    public function test_user_with_daily_setting_but_no_events_does_not_get_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_daily_update' => 1,
        ]);

        $this->artisan('notify')->assertExitCode(0);

        Mail::assertNotSent(DailyReminder::class);
    }
}
