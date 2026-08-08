<?php

namespace App\Listeners;

use App\Events\EventCreated;
use App\Events\EventPhotoAdded;
use App\Events\EventUpdated;
use App\Jobs\Discord\PostEventToDiscord;
use App\Models\DiscordPost;
use App\Models\Event;

/**
 * Queues the Discord announcement for a new event (issue #2058).
 *
 * Runs synchronously and does almost nothing — it decides whether to dispatch,
 * and dispatches. Queueing the listener itself would only add a hop before the
 * job it queues.
 *
 * The delay is what makes this usable in practice: admins routinely fix a
 * title, venue or flyer within minutes of creating an event, and announcing
 * instantly would broadcast the typos. PostEventToDiscord is ShouldBeUnique,
 * so the create → photo-added → updated burst collapses into one job.
 */
class QueueDiscordEventPost
{
    public function handle(EventCreated|EventPhotoAdded|EventUpdated $domainEvent): void
    {
        if (! config('discord.enabled')) {
            return;
        }

        $event = $domainEvent->event;

        if (! $event instanceof Event || null === $event->getKey()) {
            return;
        }

        // Cheap pre-filter only. The job re-resolves targets when it runs,
        // half an hour later, by which time the event may have changed.
        if ($event->do_not_repost || null === $event->start_at || $event->start_at->isPast()) {
            return;
        }

        PostEventToDiscord::dispatch($event->getKey(), DiscordPost::REASON_CREATE)
            ->delay(now()->addMinutes((int) config('discord.create_delay_minutes', 30)));
    }
}
