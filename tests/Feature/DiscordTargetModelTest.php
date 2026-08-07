<?php

namespace Tests\Feature;

use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Foundation of the Discord auto-repost feature (issue #2058).
 *
 * Two things here are load-bearing beyond ordinary model behaviour:
 *
 *  - the webhook URL is a bearer credential, so its encryption at rest and
 *    its absence from serialisation are security properties, not conveniences;
 *  - the discord_posts unique index IS the idempotency mechanism every posting
 *    mode relies on, so it is tested at the database level rather than assumed.
 */
class DiscordTargetModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const WEBHOOK = 'https://discord.com/api/webhooks/123456789012345678/s3cr3tT0k3nValue';

    public function test_webhook_url_is_encrypted_at_rest_but_round_trips(): void
    {
        $target = DiscordTarget::factory()->create(['webhook_url' => self::WEBHOOK]);

        $raw = DB::table('discord_targets')->where('id', $target->id)->value('webhook_url');

        $this->assertNotSame(self::WEBHOOK, $raw);
        $this->assertStringNotContainsString('s3cr3tT0k3nValue', (string) $raw);
        $this->assertSame(self::WEBHOOK, $target->fresh()->webhook_url);
    }

    public function test_webhook_url_never_appears_in_serialised_output(): void
    {
        $target = DiscordTarget::factory()->create(['webhook_url' => self::WEBHOOK]);

        $this->assertStringNotContainsString('s3cr3tT0k3nValue', json_encode($target->toArray()));
        $this->assertArrayNotHasKey('webhook_url', $target->toArray());
    }

    public function test_masked_url_shows_the_id_but_not_the_token(): void
    {
        $target = DiscordTarget::factory()->create(['webhook_url' => self::WEBHOOK]);

        $masked = $target->maskedUrl();

        $this->assertStringContainsString('123456789012345678', $masked);
        $this->assertStringNotContainsString('s3cr3tT0k3nValue', $masked);
    }

    public function test_the_same_webhook_cannot_be_registered_twice(): void
    {
        DiscordTarget::factory()->create(['webhook_url' => self::WEBHOOK]);

        // Encryption is non-deterministic, so this is caught by the sha256
        // fingerprint rather than by a unique index on the URL itself.
        $this->expectException(QueryException::class);

        DiscordTarget::factory()->create(['webhook_url' => self::WEBHOOK]);
    }

    public function test_reminder_offsets_fall_back_to_config_and_sort_descending(): void
    {
        config(['discord.default_reminder_offsets' => [7, 1]]);

        $default = DiscordTarget::factory()->create(['reminder_offsets' => null]);
        $this->assertSame([7, 1], $default->effectiveReminderOffsets());

        $custom = DiscordTarget::factory()->create(['reminder_offsets' => [1, 14, 1, -3]]);
        $this->assertSame([14, 1], $custom->effectiveReminderOffsets());
    }

    public function test_for_mode_scope_respects_per_mode_opt_in(): void
    {
        $creates = DiscordTarget::factory()->create(['post_on_create' => true, 'post_digest' => false]);
        $digests = DiscordTarget::factory()->create(['post_on_create' => false, 'post_digest' => true]);
        DiscordTarget::factory()->disabled()->create(['post_on_create' => true]);

        $this->assertSame(
            [$creates->id],
            DiscordTarget::forMode(DiscordTarget::MODE_CREATE)->pluck('id')->all()
        );
        $this->assertSame(
            [$digests->id],
            DiscordTarget::forMode(DiscordTarget::MODE_DIGEST)->pluck('id')->all()
        );

        // An unknown mode must match nothing rather than everything.
        $this->assertCount(0, DiscordTarget::forMode('nonsense')->get());
    }

    public function test_permanent_failures_disable_the_target_at_the_threshold(): void
    {
        config(['discord.auto_disable_after_failures' => 3]);

        $target = DiscordTarget::factory()->create();

        // Transient failures must not count, or one Discord outage would take
        // every channel offline at once.
        $target->recordFailure('timeout', permanent: false);
        $target->recordFailure('timeout', permanent: false);
        $this->assertSame(0, $target->fresh()->consecutive_failures);
        $this->assertTrue($target->fresh()->is_enabled);

        $target->recordFailure('bad request', permanent: true);
        $target->recordFailure('bad request', permanent: true);
        $this->assertTrue($target->fresh()->is_enabled);

        $target->recordFailure('bad request', permanent: true);
        $this->assertFalse($target->fresh()->is_enabled);
        $this->assertNotNull($target->fresh()->disabled_at);
    }

    public function test_a_deleted_webhook_disables_the_target_immediately(): void
    {
        $target = DiscordTarget::factory()->create();

        $target->recordFailure('Unknown Webhook (10015)', permanent: true, immediate: true);

        $this->assertFalse($target->fresh()->is_enabled);
        $this->assertStringContainsString('10015', (string) $target->fresh()->disabled_reason);
    }

    public function test_success_clears_the_failure_streak(): void
    {
        $target = DiscordTarget::factory()->create();
        $target->recordFailure('bad request', permanent: true);

        $target->recordSuccess();

        $this->assertSame(0, $target->fresh()->consecutive_failures);
        $this->assertNull($target->fresh()->last_error);
        $this->assertNotNull($target->fresh()->last_success_at);
    }

    public function test_ledger_unique_index_blocks_a_duplicate_post(): void
    {
        $target = DiscordTarget::factory()->create();
        $event = Event::factory()->create();

        DiscordPost::factory()->create([
            'discord_target_id' => $target->id,
            'event_id' => $event->id,
            'reason' => DiscordPost::REASON_CREATE,
            'reason_key' => '',
        ]);

        $this->expectException(QueryException::class);

        DiscordPost::factory()->create([
            'discord_target_id' => $target->id,
            'event_id' => $event->id,
            'reason' => DiscordPost::REASON_CREATE,
            'reason_key' => '',
        ]);
    }

    public function test_reason_key_separates_legitimate_repeats(): void
    {
        $target = DiscordTarget::factory()->create();
        $event = Event::factory()->create();

        // Same event and target, different reasons and offsets — all allowed.
        DiscordPost::factory()->create([
            'discord_target_id' => $target->id, 'event_id' => $event->id,
            'reason' => DiscordPost::REASON_CREATE, 'reason_key' => '',
        ]);
        DiscordPost::factory()->reminder(7)->create([
            'discord_target_id' => $target->id, 'event_id' => $event->id,
        ]);
        DiscordPost::factory()->reminder(1)->create([
            'discord_target_id' => $target->id, 'event_id' => $event->id,
        ]);

        $this->assertSame(3, DiscordPost::where('event_id', $event->id)->count());
    }

    public function test_digest_rows_dedupe_per_week_despite_carrying_no_event(): void
    {
        $target = DiscordTarget::factory()->create();

        // event_id 0 rather than NULL: MySQL treats NULLs as distinct in a
        // unique index, which would silently disable dedupe for digests.
        DiscordPost::factory()->digest('2026-W32')->create(['discord_target_id' => $target->id]);
        DiscordPost::factory()->digest('2026-W33')->create(['discord_target_id' => $target->id]);

        $this->assertSame(2, DiscordPost::where('reason', DiscordPost::REASON_DIGEST)->count());

        $this->expectException(QueryException::class);

        DiscordPost::factory()->digest('2026-W33')->create(['discord_target_id' => $target->id]);
    }

    public function test_criteria_cascade_when_a_target_is_removed(): void
    {
        $target = DiscordTarget::factory()->create();
        DiscordTargetCriterion::factory()->count(2)
            ->sequence(['criteria_id' => 1], ['criteria_id' => 2])
            ->create(['discord_target_id' => $target->id]);

        $this->assertSame(2, $target->criteria()->count());

        $target->forceDelete();

        $this->assertSame(0, DiscordTargetCriterion::where('discord_target_id', $target->id)->count());
    }

    public function test_criterion_labels_resolve_the_referenced_record(): void
    {
        $event = Event::factory()->create();
        $venue = $event->venue;

        $criterion = DiscordTargetCriterion::factory()
            ->ofType(DiscordTargetCriterion::TYPE_VENUE, $venue->id)
            ->create();

        $this->assertSame($venue->name, $criterion->label());
        $this->assertSame('Venue', $criterion->typeLabel());

        // A criterion pointing at a since-deleted record must not explode.
        $orphan = DiscordTargetCriterion::factory()
            ->ofType(DiscordTargetCriterion::TYPE_TAG, 999999)
            ->create();
        $this->assertSame('#999999', $orphan->label());
    }
}
