<?php

namespace App\Services\Integrations\Discord;

use App\Exceptions\DiscordWebhookException;
use App\Mail\DiscordPostFailure;
use App\Models\Activity;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Orchestrates posting to a Discord channel (issue #2058).
 *
 * IDEMPOTENCY IS CLAIM-FIRST. Every send takes a discord_posts row before it
 * touches the network, under the unique index on
 * (discord_target_id, event_id, reason, reason_key). A row already marked sent
 * ends the call. This is what makes it safe for a listener and a scheduled
 * command to fire at the same instant, and for a job to be retried after a
 * partial success — neither can produce a second message.
 *
 * Contrast AutomateInstagramPosts, which reimplements its poster's logic
 * inline and dedupes with an unconstrained ledger read.
 */
class DiscordEventPoster
{
    public function __construct(
        private readonly DiscordWebhookClient $client,
        private readonly DiscordEmbedBuilder $builder,
    ) {}

    /**
     * Post a single event. Returns the ledger row in whatever state it ended
     * up — including 'sent' when a previous call already delivered it.
     *
     * @throws DiscordWebhookException
     */
    public function post(
        Event $event,
        DiscordTarget $target,
        string $reason,
        string $reasonKey = '',
        ?int $userId = null,
    ): DiscordPost {
        return $this->deliver(
            $target,
            (int) $event->getKey(),
            $reason,
            $reasonKey,
            fn (): array => $this->builder->forEvent($event, $target),
            $userId,
            $event,
        );
    }

    /**
     * Post a weekly roundup. Carries event_id 0 because it is about a window,
     * not one event.
     *
     * @param  Collection<int, Event>  $events
     *
     * @throws DiscordWebhookException
     */
    public function postDigest(
        DiscordTarget $target,
        Collection $events,
        string $weekKey,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $userId = null,
    ): DiscordPost {
        if ($events->isEmpty()) {
            // Record the empty week so the hourly command stops reconsidering
            // it — but never downgrade a digest that already went out.
            $post = $this->claim($target, 0, DiscordPost::REASON_DIGEST, $weekKey, $userId);

            if (! $post->isSent()) {
                $post->fill(['status' => DiscordPost::STATUS_SKIPPED])->save();
            }

            return $post;
        }

        return $this->deliver(
            $target,
            0,
            DiscordPost::REASON_DIGEST,
            $weekKey,
            fn (): array => $this->builder->forDigest($target, $events, $from, $to),
            $userId,
        );
    }

    /**
     * Send a connection test, so an admin can verify a webhook before any real
     * event depends on it. Deliberately re-sendable: the ledger row is reused
     * rather than blocking a second test.
     *
     * @throws DiscordWebhookException
     */
    public function sendTest(DiscordTarget $target, ?int $userId = null): DiscordPost
    {
        return $this->deliver(
            $target,
            0,
            DiscordPost::REASON_TEST,
            '',
            fn (): array => $this->builder->forTest($target),
            $userId,
            null,
            allowResend: true,
        );
    }

    /**
     * The shared claim → build → send → record path.
     *
     * @param  callable(): array<string, mixed>  $payloadFactory
     *
     * @throws DiscordWebhookException
     */
    private function deliver(
        DiscordTarget $target,
        int $eventId,
        string $reason,
        string $reasonKey,
        callable $payloadFactory,
        ?int $userId,
        ?Event $event = null,
        bool $allowResend = false,
    ): DiscordPost {
        $post = $this->claim($target, $eventId, $reason, $reasonKey, $userId);

        if ($post->isSent() && ! $allowResend) {
            Log::debug('Discord: already sent, no-op', [
                'target_id' => $target->id,
                'event_id' => $eventId,
                'reason' => $reason,
                'reason_key' => $reasonKey,
            ]);

            return $post;
        }

        if (! config('discord.enabled') || ! $target->is_enabled) {
            $reasonSkipped = config('discord.enabled') ? 'Target disabled' : 'Discord integration disabled';

            $post->fill([
                'status' => DiscordPost::STATUS_SKIPPED,
                'error' => $reasonSkipped,
            ])->save();

            Log::info('Discord: post skipped', [
                'target_id' => $target->id,
                'target' => $target->name,
                'event_id' => $eventId,
                'reason' => $reason,
                'skipped_because' => $reasonSkipped,
            ]);

            return $post;
        }

        $payload = $payloadFactory();

        try {
            $result = $this->client->send($target->webhook_url, $payload);
        } catch (DiscordWebhookException $e) {
            $this->recordFailure($post, $target, $e, $eventId, $reason);

            throw $e;
        }

        $post->fill([
            'status' => DiscordPost::STATUS_SENT,
            'message_id' => $result['message_id'],
            'channel_id' => $result['channel_id'],
            'payload_hash' => sha1((string) json_encode($payload)),
            'attempts' => $post->attempts + 1,
            'error' => null,
            'posted_at' => now(),
        ])->save();

        $target->recordSuccess();

        // The counterpart to the failure line below. Without it the log can
        // only ever prove that something went wrong, never that anything went
        // right, which is the harder question when a channel looks quiet.
        Log::info('Discord post sent', [
            'target_id' => $target->id,
            'target' => $target->name,
            'event_id' => $eventId,
            'reason' => $reason,
            'reason_key' => $reasonKey,
            'message_id' => $result['message_id'],
        ]);

        if (null !== $event) {
            $this->recordEventSuccess($event, $result['message_id'], $userId);
        }

        return $post;
    }

    /**
     * Take the ledger row before going near the network.
     *
     * firstOrCreate races against a concurrent claim, so the duplicate-key
     * error is caught and the winning row re-read — that is the unique index
     * doing its job, not a failure.
     */
    private function claim(
        DiscordTarget $target,
        int $eventId,
        string $reason,
        string $reasonKey,
        ?int $userId,
    ): DiscordPost {
        $keys = [
            'discord_target_id' => $target->id,
            'event_id' => $eventId,
            'reason' => $reason,
            'reason_key' => $reasonKey,
        ];

        try {
            return DiscordPost::firstOrCreate($keys, [
                'status' => DiscordPost::STATUS_PENDING,
                'created_by' => $userId,
            ]);
        } catch (QueryException $e) {
            $existing = DiscordPost::where($keys)->first();

            if (null === $existing) {
                throw $e;
            }

            return $existing;
        }
    }

    /**
     * Mirror a successful post into event_shares and the activity log, so the
     * existing "where has this been shared" reporting stays uniform across
     * platforms. Non-authoritative — discord_posts is the real ledger.
     */
    private function recordEventSuccess(Event $event, ?string $messageId, ?int $userId): void
    {
        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'discord',
            'platform_id' => $messageId,
            'created_by' => $userId,
            'posted_at' => now(),
        ]);

        // Log the Event, never the DiscordTarget: Activity::log() serialises
        // the whole model into activities.changes, which would put a decrypted
        // webhook URL into an admin-readable table.
        Activity::log($event, $userId ? User::find($userId) : null, 20);
    }

    private function recordFailure(
        DiscordPost $post,
        DiscordTarget $target,
        DiscordWebhookException $e,
        int $eventId,
        string $reason,
    ): void {
        $post->fill([
            'status' => DiscordPost::STATUS_FAILED,
            'attempts' => $post->attempts + 1,
            'error' => Str::limit($e->getMessage(), 480),
        ])->save();

        $target->recordFailure(
            $e->getMessage(),
            permanent: $e->permanent,
            immediate: $e->isUnknownWebhook(),
        );

        // maskedUrl(), never the URL itself — it is a bearer credential and
        // this line reaches the log file.
        Log::error('Discord post failed', [
            'target_id' => $target->id,
            'target' => $target->name,
            'webhook' => $target->maskedUrl(),
            'event_id' => $eventId,
            'reason' => $reason,
            'status' => $e->status,
            'discord_code' => $e->discordCode,
            'permanent' => $e->permanent,
            'error' => $e->getMessage(),
        ]);

        if ($e->permanent) {
            $this->notifyAdmin($target, $e);
        }
    }

    /**
     * Email an admin about a permanent failure, at most hourly per target — a
     * bad night must not flood the inbox. Mail failure must never propagate
     * and take down the post path with it.
     */
    private function notifyAdmin(DiscordTarget $target, DiscordWebhookException $e): void
    {
        if (! config('discord.notify_admin_on_failure')) {
            return;
        }

        if (! Cache::add('discord-failure-mail-'.$target->id, true, 3600)) {
            return;
        }

        try {
            Mail::send(new DiscordPostFailure(
                $target->name,
                $target->maskedUrl(),
                $e->getMessage(),
                ! $target->fresh()?->is_enabled,
                (string) config('app.app_name'),
                (string) config('app.admin'),
                (string) config('app.noreplyemail'),
            ));
        } catch (\Exception $mailException) {
            Log::error('Discord: failed to send failure email: '.$mailException->getMessage());
        }
    }
}
