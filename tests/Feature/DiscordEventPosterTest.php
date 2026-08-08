<?php

namespace Tests\Feature;

use App\Exceptions\DiscordWebhookException;
use App\Mail\DiscordPostFailure;
use App\Models\Activity;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\EventShare;
use App\Services\Integrations\Discord\DiscordEventPoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Delivery, idempotency and failure handling (issue #2058).
 *
 * The double-post assertions are the point of this file. Reposting the same
 * event into a channel is the most visible way this feature can misbehave, and
 * the ledger's unique index — not a best-effort lookup — is what prevents it.
 */
class DiscordEventPosterTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['discord.enabled' => true]);
    }

    private function poster(): DiscordEventPoster
    {
        return app(DiscordEventPoster::class);
    }

    private function fakeSuccess(): void
    {
        Http::fake(['*' => Http::response(['id' => '900', 'channel_id' => '800'], 200)]);
    }

    public function test_a_successful_post_records_the_ledger_row(): void
    {
        $this->fakeSuccess();

        $event = Event::factory()->create();
        $target = DiscordTarget::factory()->create();

        $post = $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);

        $this->assertTrue($post->isSent());
        $this->assertSame('900', $post->message_id);
        $this->assertSame('800', $post->channel_id);
        $this->assertNotNull($post->posted_at);
        $this->assertNotNull($post->payload_hash);
        $this->assertNotNull($target->fresh()->last_success_at);
    }

    public function test_posting_the_same_event_twice_sends_only_once(): void
    {
        $this->fakeSuccess();

        $event = Event::factory()->create();
        $target = DiscordTarget::factory()->create();

        $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);
        $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);

        Http::assertSentCount(1);
        $this->assertSame(1, DiscordPost::where('event_id', $event->id)->count());
    }

    public function test_a_different_reason_is_a_different_post(): void
    {
        $this->fakeSuccess();

        $event = Event::factory()->create();
        $target = DiscordTarget::factory()->create();

        $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);
        $this->poster()->post($event, $target, DiscordPost::REASON_REMINDER, '7');
        $this->poster()->post($event, $target, DiscordPost::REASON_REMINDER, '1');

        Http::assertSentCount(3);
        $this->assertSame(3, DiscordPost::where('event_id', $event->id)->count());
    }

    public function test_a_successful_post_mirrors_into_event_shares_and_activity(): void
    {
        $this->fakeSuccess();

        $event = Event::factory()->create();

        $this->poster()->post($event, DiscordTarget::factory()->create(), DiscordPost::REASON_CREATE);

        $this->assertDatabaseHas('event_shares', [
            'event_id' => $event->id,
            'platform' => 'discord',
            'platform_id' => '900',
        ]);

        // Action 20 is inserted by the migration; Activity::log() would 500
        // on an id the actions table does not carry.
        $this->assertSame(1, Activity::where('object_id', $event->id)->where('action_id', 20)->count());
    }

    public function test_nothing_is_sent_while_the_feature_is_switched_off(): void
    {
        config(['discord.enabled' => false]);
        Http::fake();

        $post = $this->poster()->post(
            Event::factory()->create(),
            DiscordTarget::factory()->create(),
            DiscordPost::REASON_CREATE
        );

        Http::assertNothingSent();
        $this->assertSame(DiscordPost::STATUS_SKIPPED, $post->status);
    }

    public function test_nothing_is_sent_to_a_disabled_target(): void
    {
        Http::fake();

        $post = $this->poster()->post(
            Event::factory()->create(),
            DiscordTarget::factory()->disabled()->create(),
            DiscordPost::REASON_CREATE
        );

        Http::assertNothingSent();
        $this->assertSame(DiscordPost::STATUS_SKIPPED, $post->status);
    }

    public function test_a_failure_is_recorded_and_rethrown(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['message' => 'Invalid Form Body', 'code' => 50035], 400)]);

        $event = Event::factory()->create();
        $target = DiscordTarget::factory()->create();

        try {
            $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);
            $this->fail('Expected a DiscordWebhookException.');
        } catch (DiscordWebhookException $e) {
            $this->assertTrue($e->permanent);
        }

        $post = DiscordPost::where('event_id', $event->id)->firstOrFail();
        $this->assertSame(DiscordPost::STATUS_FAILED, $post->status);
        $this->assertStringContainsString('Invalid Form Body', (string) $post->error);
        $this->assertSame(1, $target->fresh()->consecutive_failures);

        // No message went out, so nothing should claim one did.
        $this->assertDatabaseMissing('event_shares', ['event_id' => $event->id, 'platform' => 'discord']);
    }

    public function test_a_failed_post_can_be_retried(): void
    {
        Mail::fake();
        Http::fake([
            '*' => Http::sequence()
                ->push(['message' => 'Service Unavailable'], 503)
                ->push(['message' => 'Service Unavailable'], 503)
                ->push(['message' => 'Service Unavailable'], 503)
                ->push(['id' => '901'], 200),
        ]);

        $event = Event::factory()->create();
        $target = DiscordTarget::factory()->create();

        try {
            $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);
        } catch (DiscordWebhookException) {
            // expected
        }

        // A recorded failure must not permanently block the retry — the reason
        // event_shares' posted_at-is-null convention was not reused here.
        $post = $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);

        $this->assertTrue($post->isSent());
        $this->assertSame(1, DiscordPost::where('event_id', $event->id)->count());
    }

    public function test_a_deleted_webhook_disables_the_target_and_emails_an_admin(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['message' => 'Unknown Webhook', 'code' => 10015], 404)]);

        $target = DiscordTarget::factory()->create();

        try {
            $this->poster()->post(Event::factory()->create(), $target, DiscordPost::REASON_CREATE);
        } catch (DiscordWebhookException) {
            // expected
        }

        $this->assertFalse($target->fresh()->is_enabled);
        Mail::assertSent(DiscordPostFailure::class);
    }

    public function test_admin_failure_email_is_throttled_per_target(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['message' => 'Invalid Form Body', 'code' => 50035], 400)]);

        $target = DiscordTarget::factory()->create();

        foreach (Event::factory()->count(3)->create() as $event) {
            try {
                $this->poster()->post($event, $target, DiscordPost::REASON_CREATE);
            } catch (DiscordWebhookException) {
                // expected
            }
        }

        // A bad night must not flood the inbox.
        Mail::assertSentCount(1);
    }

    public function test_an_empty_digest_week_is_recorded_without_sending(): void
    {
        Http::fake();

        $target = DiscordTarget::factory()->digest()->create();

        $post = $this->poster()->postDigest(
            $target,
            collect(),
            '2026-W32',
            now(),
            now()->addWeek(),
        );

        Http::assertNothingSent();
        $this->assertSame(DiscordPost::STATUS_SKIPPED, $post->status);
    }

    public function test_a_digest_is_sent_once_per_week(): void
    {
        $this->fakeSuccess();

        $target = DiscordTarget::factory()->digest()->create();
        $events = Event::factory()->count(2)->create();

        $this->poster()->postDigest($target, $events, '2026-W32', now(), now()->addWeek());
        $this->poster()->postDigest($target, $events, '2026-W32', now(), now()->addWeek());
        $this->poster()->postDigest($target, $events, '2026-W33', now(), now()->addWeek());

        Http::assertSentCount(2);
    }

    public function test_a_sent_digest_is_not_downgraded_by_a_later_empty_run(): void
    {
        $this->fakeSuccess();

        $target = DiscordTarget::factory()->digest()->create();

        $this->poster()->postDigest($target, Event::factory()->count(2)->create(), '2026-W32', now(), now()->addWeek());
        $post = $this->poster()->postDigest($target, collect(), '2026-W32', now(), now()->addWeek());

        $this->assertTrue($post->isSent());
    }

    public function test_a_test_message_can_be_sent_repeatedly(): void
    {
        $this->fakeSuccess();

        $target = DiscordTarget::factory()->create();

        $this->poster()->sendTest($target);
        $this->poster()->sendTest($target);

        // Unlike an event post, re-testing a webhook is a legitimate thing to
        // do twice, and must not be swallowed as a duplicate.
        Http::assertSentCount(2);
        $this->assertSame(1, DiscordPost::where('reason', DiscordPost::REASON_TEST)->count());
    }
}
