<?php

namespace Tests\Feature\Console;

use App\Jobs\Discord\PostEventToDiscord;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * discord:reminders (issue #2058).
 *
 * The window is day-granular against a once-daily run, so the two failure
 * modes worth testing are an event slipping between runs and an event being
 * caught by two.
 */
class PostDiscordRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['discord.enabled' => true, 'discord.reminder_min_gap_hours' => 36]);
    }

    private function eventStartingIn(int $days): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            // Midday, so a whole-day window is unambiguous.
            'start_at' => now()->addDays($days)->setTime(20, 0),
            'do_not_repost' => false,
        ]);

        $event->photos()->attach(Photo::factory()->create(['is_primary' => 1])->id);

        return $event->fresh();
    }

    public function test_only_events_at_a_configured_offset_are_reminded(): void
    {
        Queue::fake();

        DiscordTarget::factory()->matchAll()->reminders([7, 1])->create();

        $inSeven = $this->eventStartingIn(7);
        $inOne = $this->eventStartingIn(1);
        $this->eventStartingIn(3);

        $this->artisan('discord:reminders')->assertSuccessful();

        Queue::assertPushed(PostEventToDiscord::class, 2);
        Queue::assertPushed(PostEventToDiscord::class, fn ($job): bool => $job->eventId === $inSeven->id && '7' === $job->reasonKey);
        Queue::assertPushed(PostEventToDiscord::class, fn ($job): bool => $job->eventId === $inOne->id && '1' === $job->reasonKey);
    }

    public function test_a_second_run_the_same_day_sends_nothing_new(): void
    {
        // QUEUE_DRIVER is sync under phpunit, so this run posts for real.
        Http::fake(['*' => Http::response(['id' => '1'], 200)]);

        DiscordTarget::factory()->matchAll()->reminders([7])->create();
        $event = $this->eventStartingIn(7);

        $this->artisan('discord:reminders')->assertSuccessful();
        $this->assertSame(1, DiscordPost::where('event_id', $event->id)->count());
        Http::assertSentCount(1);

        // The recency guard catches this one before a job is even dispatched.
        $this->artisan('discord:reminders')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame(1, DiscordPost::where('event_id', $event->id)->count());
    }

    public function test_the_ledger_still_dedupes_after_the_recency_guard_expires(): void
    {
        Http::fake(['*' => Http::response(['id' => '1'], 200)]);

        DiscordTarget::factory()->matchAll()->reminders([7])->create();
        $event = $this->eventStartingIn(7);

        $this->artisan('discord:reminders')->assertSuccessful();
        Http::assertSentCount(1);

        // Past the 36-hour guard, so the command re-queues — and the unique
        // index on (target, event, 'reminder', '7') is the only thing left
        // preventing a second message. It has to hold on its own.
        $this->travel(3)->days();
        $event->update(['start_at' => now()->addDays(7)->setTime(20, 0)]);

        $this->artisan('discord:reminders')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertSame(1, DiscordPost::where('event_id', $event->id)->count());
    }

    public function test_a_reminder_is_suppressed_right_after_an_announcement(): void
    {
        Queue::fake();

        $target = DiscordTarget::factory()->matchAll()->reminders([7])->create();
        $event = $this->eventStartingIn(7);

        // Announced yesterday when it was added at T-8d; reminding today would
        // be the same message twice in two days.
        DiscordPost::factory()->create([
            'discord_target_id' => $target->id,
            'event_id' => $event->id,
            'reason' => DiscordPost::REASON_CREATE,
            'posted_at' => now()->subHours(20),
        ]);

        $this->artisan('discord:reminders')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_dry_run_queues_nothing(): void
    {
        Queue::fake();

        DiscordTarget::factory()->matchAll()->reminders([7])->create();
        $this->eventStartingIn(7);

        $this->artisan('discord:reminders', ['--dry-run' => true])->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_targets_not_opted_into_reminders_are_skipped(): void
    {
        Queue::fake();

        DiscordTarget::factory()->matchAll()->create(['post_reminders' => false]);
        $this->eventStartingIn(7);

        $this->artisan('discord:reminders')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_nothing_runs_while_the_feature_is_switched_off(): void
    {
        config(['discord.enabled' => false]);
        Queue::fake();

        DiscordTarget::factory()->matchAll()->reminders([7])->create();
        $this->eventStartingIn(7);

        $this->artisan('discord:reminders')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_a_target_can_be_isolated_for_a_manual_run(): void
    {
        Queue::fake();

        $chosen = DiscordTarget::factory()->matchAll()->reminders([7])->create();
        DiscordTarget::factory()->matchAll()->reminders([7])->create();
        $this->eventStartingIn(7);

        $this->artisan('discord:reminders', ['--target' => $chosen->id])->assertSuccessful();

        Queue::assertPushed(PostEventToDiscord::class, 1);
        Queue::assertPushed(PostEventToDiscord::class, fn ($job): bool => [$chosen->id] === $job->targetIds);
    }
}
