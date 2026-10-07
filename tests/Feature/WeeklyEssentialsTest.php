<?php

namespace Tests\Feature;

use App\Mail\DailyReminder;
use App\Mail\DigestsPaused;
use App\Mail\WeeklyEssentials;
use App\Mail\WeeklyUpdate;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventReachDaily;
use App\Models\Follow;
use App\Models\Profile;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\DigestBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The weekly digest's Essential Events fallback for subscribers whose own
 * digest would be empty (#2102).
 */
class WeeklyEssentialsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        // pinned mid-week so "the coming week" is unambiguous
        $this->travelTo(Carbon::parse('2026-10-14 12:00:00'));
    }

    /** A new (so engaged) subscriber who follows nothing. */
    private function subscriber(int $weekly = 1, int $daily = 0, int $accountAgeDays = 10): User
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'created_at' => Carbon::now()->subDays($accountAgeDays),
        ]);
        Profile::factory()->create(['user_id' => $user->id, 'setting_weekly_update' => $weekly, 'setting_daily_update' => $daily]);

        return $user->fresh('profile');
    }

    private function event(string $name, int $daysAhead, int $attendees = 0, int $visibility = Visibility::VISIBILITY_PUBLIC, ?Carbon $cancelled = null): Event
    {
        $event = Event::factory()->create([
            'name' => $name,
            'start_at' => Carbon::now()->addDays($daysAhead),
            'visibility_id' => $visibility,
            'cancelled_at' => $cancelled,
        ]);

        $attending = DB::table('response_types')->where('name', 'Attending')->value('id');
        for ($i = 0; $i < $attendees; $i++) {
            $event->eventResponses()->create(['user_id' => User::factory()->create()->id, 'response_type_id' => $attending]);
        }

        return $event;
    }

    /** @return WeeklyEssentials|null the fallback sent to $user */
    private function essentialsFor(User $user): ?WeeklyEssentials
    {
        return Mail::sent(WeeklyEssentials::class, fn (WeeklyEssentials $mail) => $mail->hasTo($user->email))->first();
    }

    public function test_a_subscriber_with_nothing_personal_gets_the_essential_events_email(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->event('Zz Popular Show', 3, attendees: 2);

        $this->artisan('notifyWeekly')->assertExitCode(0);

        Mail::assertNotSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
        $mail = $this->essentialsFor($user);
        $this->assertNotNull($mail, 'expected the Essential Events email');

        $html = $mail->render();
        $this->assertStringContainsString('Essential events this week', $mail->subject);
        $this->assertStringContainsString('Zz Popular Show', $html);
        $this->assertStringContainsString('Make this email yours', $html);
        $this->assertStringContainsString('to=%2Fonboarding', $html);
    }

    public function test_a_subscriber_with_follows_still_gets_their_personal_digest(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $entity = Entity::factory()->create();
        Follow::create(['user_id' => $user->id, 'object_type' => 'entity', 'object_id' => $entity->id]);
        $this->event('Zz Followed Show', 3)->entities()->attach($entity->id);
        $this->event('Zz Popular Show', 3, attendees: 5);

        $this->artisan('notifyWeekly');

        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertNull($this->essentialsFor($user));
    }

    public function test_a_dormant_subscriber_with_nothing_personal_gets_nothing_at_all(): void
    {
        // the fallback must not turn into a paused notice about mail they never got
        Mail::fake();
        $user = $this->subscriber(accountAgeDays: 365);
        $this->event('Zz Popular Show', 3, attendees: 2);

        $this->artisan('notifyWeekly');

        $this->assertNull($this->essentialsFor($user));
        Mail::assertNotSent(DigestsPaused::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertNull($user->profile->fresh()->digests_paused_at);
    }

    public function test_the_daily_digest_stays_personal(): void
    {
        Mail::fake();
        $user = $this->subscriber(weekly: 0, daily: 1);
        $this->event('Zz Popular Today', 0, attendees: 2);

        $this->artisan('notify');

        Mail::assertNotSent(DailyReminder::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertNull($this->essentialsFor($user));
    }

    public function test_no_email_when_there_are_no_upcoming_events(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->event('Zz Next Month', 30, attendees: 3);

        $this->artisan('notifyWeekly');

        $this->assertTrue(Mail::sent(fn (\Illuminate\Mail\Mailable $mail) => $mail->hasTo($user->email))->isEmpty());
    }

    public function test_essentials_are_this_weeks_visible_uncancelled_events_most_popular_first(): void
    {
        $user = $this->subscriber();
        $this->event('Zz Quiet', 2, attendees: 0);
        $this->event('Zz Busy', 5, attendees: 3);
        $this->event('Zz Middling', 1, attendees: 1);
        $this->event('Zz Private', 2, attendees: 9, visibility: Visibility::VISIBILITY_PRIVATE);
        $this->event('Zz Cancelled', 2, attendees: 9, cancelled: Carbon::now());
        $this->event('Zz Too Far', 9, attendees: 9);

        $names = app(DigestBuilder::class)->essentials($user)->events->pluck('name')->all();

        $this->assertSame(['Zz Busy', 'Zz Middling', 'Zz Quiet'], array_values(array_filter($names, fn ($n) => str_starts_with($n, 'Zz '))));
    }

    public function test_suggestions_leave_out_what_the_user_already_follows(): void
    {
        $user = $this->subscriber();
        $followed = Tag::factory()->create(['name' => 'Zzfollowedtag', 'slug' => 'zzfollowedtag']);
        $other = Tag::factory()->create(['name' => 'Zzothertag', 'slug' => 'zzothertag']);
        foreach ([$followed, $other] as $tag) {
            // make both popular
            $this->event('Zz Tagged '.$tag->name, 3)->tags()->attach($tag->id);
        }
        Follow::create(['user_id' => $user->id, 'object_type' => 'tag', 'object_id' => $followed->id]);

        $tagIds = app(DigestBuilder::class)->essentials($user)->tags->pluck('id')->all();

        $this->assertNotContains($followed->id, $tagIds);
    }

    public function test_the_fallback_counts_toward_digest_reach(): void
    {
        Mail::fake();
        Profile::query()->update(['setting_weekly_update' => 0]);
        $user = $this->subscriber();
        $event = $this->event('Zz Popular Show', 3, attendees: 2);

        $this->artisan('notifyWeekly');

        $this->assertNotNull($this->essentialsFor($user));
        $this->assertSame(1, EventReachDaily::where('event_id', $event->id)->where('channel', EventReachDaily::CHANNEL_DIGEST)->sole()->count);
    }

    public function test_dry_run_reports_the_fallback_count(): void
    {
        Mail::fake();
        Profile::query()->update(['setting_weekly_update' => 0]);
        $this->subscriber();
        $this->subscriber();
        $this->event('Zz Popular Show', 3, attendees: 2);

        $this->artisan('notifyWeekly', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN: would send 0 weekly digest(s), 2 essential events email(s)')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_send_now_weekly_sends_the_fallback_too(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->event('Zz Popular Show', 3, attendees: 2);

        $this->actingAs($user)->post("/users/{$user->id}/weekly")
            ->assertSessionHas('flash_message.level', 'success');

        $this->assertNotNull($this->essentialsFor($user));
    }

    public function test_the_essentials_email_carries_list_unsubscribe_for_the_weekly_list(): void
    {
        /** @var \Illuminate\Mail\Transport\ArrayTransport $transport */
        $transport = Mail::getSymfonyTransport();
        $transport->flush();
        $user = $this->subscriber();
        $this->event('Zz Popular Show', 3, attendees: 2);

        $url = rtrim((string) config('app.url'), '/').'/';
        Mail::to($user->email)->send(new WeeklyEssentials($url, 'TestSite', 'admin@test.app', 'noreply@test.app', $user, app(DigestBuilder::class)->essentials($user)));

        $header = $transport->messages()->last()->getOriginalMessage()->getHeaders()->get('List-Unsubscribe')?->getBodyAsString();
        $this->assertStringContainsString("/email/unsubscribe/{$user->id}/weekly?signature=", (string) $header);
    }

    public function test_the_onboarding_link_opens_the_picker(): void
    {
        $user = $this->subscriber();
        $user->profile->forceFill(['onboarding_dismissed_at' => now()])->save();

        $this->actingAs($user)->get('/onboarding')
            ->assertRedirect('/')
            ->assertSessionHas('show_onboarding', true);
    }

    public function test_the_onboarding_link_asks_guests_to_log_in(): void
    {
        $this->get('/onboarding')->assertRedirect('/login');
    }
}
