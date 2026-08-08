<?php

namespace Tests\Feature;

use App\Events\EventPhotoAdded;
use App\Jobs\Discord\PostEventToDiscord;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Visibility;
use App\Services\Integrations\Discord\DiscordEventPoster;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Automatic announcement of a new event (issue #2058).
 *
 * Covers the whole path: a photo is attached, a delayed job is queued, and
 * running it posts exactly once no matter how many times the burst of
 * create/update/photo events fires.
 */
class DiscordAutoPostTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['discord.enabled' => true, 'discord.create_delay_minutes' => 30]);
    }

    private function eligibleEvent(): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays(10),
            'do_not_repost' => false,
        ]);

        $event->photos()->attach(Photo::factory()->create(['is_primary' => 1])->id);

        return $event->fresh();
    }

    /**
     * Run the job inline, as the queue worker would.
     */
    private function runJob(int $eventId, string $reason = DiscordPost::REASON_CREATE): void
    {
        (new PostEventToDiscord($eventId, $reason))
            ->handle(app(DiscordTargetMatcher::class), app(DiscordEventPoster::class));
    }

    public function test_attaching_a_photo_queues_a_delayed_post(): void
    {
        Queue::fake();

        $event = $this->eligibleEvent();
        EventPhotoAdded::dispatch($event, true);

        Queue::assertPushed(PostEventToDiscord::class, function (PostEventToDiscord $job) use ($event): bool {
            return $job->eventId === $event->id
                && DiscordPost::REASON_CREATE === $job->reason
                // The delay is what lets an admin fix a typo before the
                // channel sees it.
                && null !== $job->delay;
        });
    }

    public function test_the_burst_of_domain_events_collapses_into_one_job(): void
    {
        $event = $this->eligibleEvent();

        // create -> photo added -> updated all fire within seconds of each
        // other in the real flow; ShouldBeUnique reduces them to one job, and
        // the ledger's unique index catches whatever slips through.
        $this->assertSame(
            'discord:create:'.$event->id.':',
            (new PostEventToDiscord($event->id, DiscordPost::REASON_CREATE))->uniqueId()
        );
    }

    public function test_running_the_job_posts_to_every_matching_channel(): void
    {
        Http::fake(['*' => Http::response(['id' => '100', 'channel_id' => '200'], 200)]);

        $event = $this->eligibleEvent();
        DiscordTarget::factory()->matchAll()->count(2)->create();

        $this->runJob($event->id);

        Http::assertSentCount(2);
        $this->assertSame(2, DiscordPost::where('event_id', $event->id)->where('status', 'sent')->count());
    }

    public function test_running_the_job_twice_never_double_posts(): void
    {
        Http::fake(['*' => Http::response(['id' => '100'], 200)]);

        $event = $this->eligibleEvent();
        DiscordTarget::factory()->matchAll()->create();

        $this->runJob($event->id);
        $this->runJob($event->id);

        // A retry after a partial success is the realistic version of this,
        // and it must not produce a second message.
        Http::assertSentCount(1);
        $this->assertSame(1, DiscordPost::where('event_id', $event->id)->count());
    }

    public function test_a_deleted_event_is_handled_quietly(): void
    {
        Http::fake();

        $event = $this->eligibleEvent();
        DiscordTarget::factory()->matchAll()->create();
        $eventId = $event->id;
        $event->delete();

        // Deleted inside the settle window. Passing an id rather than the
        // model is what lets this exit without burning three retries on a
        // ModelNotFoundException.
        $this->runJob($eventId);

        Http::assertNothingSent();
    }

    public function test_nothing_is_queued_while_the_feature_is_switched_off(): void
    {
        config(['discord.enabled' => false]);
        Queue::fake();

        EventPhotoAdded::dispatch($this->eligibleEvent(), true);

        Queue::assertNothingPushed();
    }

    public function test_an_opted_out_event_is_never_queued(): void
    {
        Queue::fake();

        $event = $this->eligibleEvent();
        $event->update(['do_not_repost' => true]);

        EventPhotoAdded::dispatch($event->fresh(), true);

        Queue::assertNothingPushed();
    }

    public function test_a_past_event_is_never_queued(): void
    {
        Queue::fake();

        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->subDays(2),
        ]);

        EventPhotoAdded::dispatch($event, true);

        Queue::assertNothingPushed();
    }

    public function test_one_failing_channel_does_not_stop_the_others(): void
    {
        $good = DiscordTarget::factory()->matchAll()->create();
        $bad = DiscordTarget::factory()->matchAll()->create();

        Http::fake([
            // Match on the id segment, which is the only part of the URL that
            // is safe to reason about outside the model.
            '*'.$bad->webhook_id.'*' => Http::response(['message' => 'Invalid Form Body', 'code' => 50035], 400),
            '*' => Http::response(['id' => '100'], 200),
        ]);

        $event = $this->eligibleEvent();

        $this->runJob($event->id);

        $this->assertSame(
            DiscordPost::STATUS_SENT,
            DiscordPost::where('discord_target_id', $good->id)->value('status')
        );
        $this->assertSame(
            DiscordPost::STATUS_FAILED,
            DiscordPost::where('discord_target_id', $bad->id)->value('status')
        );
    }
}
