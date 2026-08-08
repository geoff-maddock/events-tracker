<?php

namespace Tests\Feature\Console;

use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * discord:digest (issue #2058).
 *
 * The command is scheduled hourly and picks the targets whose configured day
 * and hour match, so "fires in the right hour and only then" and "a second run
 * in the same week is a no-op" are the assertions that make that schedule safe.
 */
class PostDiscordDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['discord.enabled' => true]);

        // A fixed instant, so "the target's hour" is unambiguous.
        // 2026-08-13 is a Thursday (dayOfWeek 4).
        $this->travelTo('2026-08-13 10:15:00');

        Http::fake(['*' => Http::response(['id' => '1', 'channel_id' => '2'], 200)]);
    }

    private function upcomingEvent(int $days = 3): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays($days),
            'do_not_repost' => false,
        ]);

        $event->photos()->attach(Photo::factory()->create(['is_primary' => 1])->id);

        return $event->fresh();
    }

    public function test_a_digest_is_posted_in_the_targets_configured_hour(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create();
        $this->upcomingEvent();
        $this->upcomingEvent(5);

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertSentCount(1);

        $post = DiscordPost::where('reason', DiscordPost::REASON_DIGEST)->firstOrFail();
        $this->assertTrue($post->isSent());
        // A digest is about a week, not an event.
        $this->assertSame(0, $post->event_id);
        $this->assertSame('2026-W33', $post->reason_key);
    }

    public function test_a_target_scheduled_for_another_hour_is_skipped(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 18)->create();
        $this->upcomingEvent();

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_a_target_scheduled_for_another_day_is_skipped(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 1, hour: 10)->create();
        $this->upcomingEvent();

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_the_roundup_lists_every_matching_event(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create();
        $events = collect([$this->upcomingEvent(1), $this->upcomingEvent(4)]);

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertSent(function ($request) use ($events): bool {
            $description = $request->data()['embeds'][0]['description'];

            foreach ($events as $event) {
                if (! str_contains($description, '/events/'.$event->slug)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_events_beyond_the_window_are_left_out(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create(['digest_window_days' => 7]);

        $inside = $this->upcomingEvent(3);
        $outside = $this->upcomingEvent(30);

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertSent(function ($request) use ($inside, $outside): bool {
            $description = $request->data()['embeds'][0]['description'];

            return str_contains($description, '/events/'.$inside->slug)
                && ! str_contains($description, '/events/'.$outside->slug);
        });
    }

    public function test_an_empty_week_records_a_skip_instead_of_retrying_hourly(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create();

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertNothingSent();

        // The row is what stops the next 23 hourly runs reconsidering it.
        $post = DiscordPost::where('reason', DiscordPost::REASON_DIGEST)->firstOrFail();
        $this->assertSame(DiscordPost::STATUS_SKIPPED, $post->status);
    }

    public function test_a_second_run_in_the_same_week_sends_nothing(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create();
        $this->upcomingEvent();

        $this->artisan('discord:digest')->assertSuccessful();
        $this->artisan('discord:digest')->assertSuccessful();

        // This is what makes an hourly schedule safe for a weekly message.
        Http::assertSentCount(1);
        $this->assertSame(1, DiscordPost::where('reason', DiscordPost::REASON_DIGEST)->count());
    }

    public function test_the_next_week_gets_its_own_digest(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create();
        $this->upcomingEvent(2);

        $this->artisan('discord:digest')->assertSuccessful();

        $this->travelTo('2026-08-20 10:15:00');
        $this->upcomingEvent(2);

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertSentCount(2);
        $this->assertSame(
            ['2026-W33', '2026-W34'],
            DiscordPost::where('reason', DiscordPost::REASON_DIGEST)->orderBy('id')->pluck('reason_key')->all()
        );
    }

    public function test_force_ignores_the_schedule_for_a_manual_run(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 1, hour: 3)->create();
        $this->upcomingEvent();

        $this->artisan('discord:digest', ['--force' => true])->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_dry_run_sends_nothing(): void
    {
        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create();
        $this->upcomingEvent();

        $this->artisan('discord:digest', ['--dry-run' => true])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, DiscordPost::count());
    }

    public function test_nothing_runs_while_the_feature_is_switched_off(): void
    {
        config(['discord.enabled' => false]);

        DiscordTarget::factory()->matchAll()->digest(day: 4, hour: 10)->create();
        $this->upcomingEvent();

        $this->artisan('discord:digest')->assertSuccessful();

        Http::assertNothingSent();
    }
}
