<?php

namespace App\Jobs\Discord;

use App\Exceptions\DiscordWebhookException;
use App\Jobs\Concerns\TracksJobStatus;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Services\Integrations\Discord\DiscordEventPoster;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Posts one event to every Discord target that wants it (issue #2058).
 *
 * Takes an event ID rather than the model. With a settle delay of half an hour
 * SerializesModels would re-fetch anyway, and an id lets an event deleted
 * inside the window exit quietly instead of throwing ModelNotFoundException
 * through all three retries.
 *
 * ShouldBeUnique collapses the create → photo-added → updated burst that
 * follows a new event into a single delayed job. It is an optimisation, not a
 * guarantee — the real protection against double-posting is the unique index
 * behind DiscordEventPoster's claim-first ledger write.
 */
class PostEventToDiscord implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksJobStatus;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    /**
     * @param  array<int, int>  $targetIds  Empty means "resolve by matching".
     */
    public function __construct(
        public int $eventId,
        public string $reason,
        public array $targetIds = [],
        public string $reasonKey = '',
        public ?int $userId = null,
    ) {
        // Only user-initiated posts get a JobStatus row. initJobStatus() is
        // unconditional in the trait, so automated posting would otherwise
        // fill job_statuses and fire completion notifications at nobody.
        if (null !== $userId) {
            $this->initJobStatus('discord_post', 'Discord post', null, $userId);
        }
    }

    public function uniqueId(): string
    {
        return 'discord:'.$this->reason.':'.$this->eventId.':'.$this->reasonKey;
    }

    public function handle(DiscordTargetMatcher $matcher, DiscordEventPoster $poster): void
    {
        if (! config('discord.enabled')) {
            return;
        }

        $this->markRunning();

        $event = Event::with(['tags', 'venue', 'eventType', 'photos'])->find($this->eventId);

        if (null === $event) {
            // Deleted between dispatch and delivery. Nothing to announce.
            $this->markSucceeded('Event no longer exists.');

            return;
        }

        $targets = $this->resolveTargets($matcher, $event);

        if ($targets->isEmpty()) {
            $this->markSucceeded('No Discord channels matched this event.');

            return;
        }

        $sent = 0;
        $failed = 0;

        foreach ($targets as $target) {
            try {
                $post = $poster->post($event, $target, $this->reason, $this->reasonKey, $this->userId);

                if ($post->isSent()) {
                    $sent++;
                }
            } catch (DiscordWebhookException $e) {
                // Caught per target so one broken channel cannot stop the rest.
                $failed++;
                Log::warning('Discord: target failed, continuing', [
                    'target_id' => $target->id,
                    'event_id' => $event->id,
                    'permanent' => $e->permanent,
                ]);
            }
        }

        $summary = $sent.' channel(s) posted'.($failed > 0 ? ', '.$failed.' failed' : '.');

        if ($failed > 0 && 0 === $sent) {
            $this->markFailed($summary);

            return;
        }

        $this->markSucceeded($summary, ['sent' => $sent, 'failed' => $failed]);
    }

    /**
     * @return Collection<int, DiscordTarget>
     */
    private function resolveTargets(DiscordTargetMatcher $matcher, Event $event): Collection
    {
        if ([] === $this->targetIds) {
            return $matcher->targetsFor($event, $this->reason);
        }

        // An admin naming a channel outranks that channel's filters — the
        // point of a manual push is to post something the filters would miss.
        // What it cannot override is the channel's own opt-out, so forMode()
        // still applies.
        /** @var Collection<int, DiscordTarget> $targets */
        $targets = DiscordTarget::forMode($this->reason)
            ->whereIn('id', $this->targetIds)
            ->with('criteria')
            ->get();

        return $targets;
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception?->getMessage() ?? 'Discord post failed.');
    }
}
