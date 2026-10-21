<?php

namespace Tests\Feature;

use App\Mail\DailyReminder;
use App\Mail\WeeklyUpdate;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The daily and weekly digests go only to active accounts with a verified
 * address, whatever the profile's digest settings say.
 */
class DigestRecipientsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Mail::fake();
    }

    /**
     * A recently active subscriber to both digests, attending an event today.
     *
     * @param array<string, mixed> $attributes
     */
    private function subscriber(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + [
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => Carbon::now()->subYear(),
        ]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_weekly_update' => 1,
            'setting_daily_update' => 1,
        ]);
        (new Activity())->forceFill([
            'user_id' => $user->id,
            'object_id' => $user->id,
            'object_table' => 'User',
            'object_name' => $user->name,
            'action_id' => Action::LOGIN,
        ])->save();

        $event = Event::factory()->create(['start_at' => Carbon::today()->setTime(12, 0), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        DB::table('event_responses')->insert([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => DB::table('response_types')->where('name', 'Attending')->value('id'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $user;
    }

    /** @return array<string, array{string, class-string}> */
    public static function digests(): array
    {
        return [
            'daily' => ['notify', DailyReminder::class],
            'weekly' => ['notifyWeekly', WeeklyUpdate::class],
        ];
    }

    /**
     * @param class-string $mailable
     */
    #[DataProvider('digests')]
    public function test_only_active_verified_accounts_get_the_digest(string $command, string $mailable): void
    {
        $active = $this->subscriber();
        $banned = $this->subscriber(['user_status_id' => UserStatus::BANNED]);
        $suspended = $this->subscriber(['user_status_id' => UserStatus::SUSPENDED]);
        // an address changed but not yet confirmed has no verified timestamp
        $unconfirmed = $this->subscriber(['email_verified_at' => null, 'email_change_pending_at' => Carbon::now()]);

        $this->artisan($command)->assertSuccessful();

        Mail::assertSent($mailable, fn ($mail) => $mail->hasTo($active->email));
        foreach ([$banned, $suspended, $unconfirmed] as $user) {
            Mail::assertNotSent($mailable, fn ($mail) => $mail->hasTo($user->email));
        }
    }
}
