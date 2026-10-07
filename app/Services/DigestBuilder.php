<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a user's daily and weekly digests, for the scheduled `notify` and
 * `notifyWeekly` commands and the "send now" actions on the user page, which
 * used to keep diverging copies of this (#2182).
 *
 * Every event comes through visible($user), so a digest never lists an event
 * its recipient can't see (#2244).
 */
class DigestBuilder
{
    private const DAILY_ATTENDING_LIMIT = 12;

    private const WEEKLY_ATTENDING_DAYS = 14;

    private const WEEKLY_SERIES_DAYS = 7;

    /**
     * Today: events the user is going to, today's events from what they follow,
     * and followed series whose next date is today.
     */
    public function daily(User $user): Digest
    {
        $attending = $user->getAttendingToday()->take(self::DAILY_ATTENDING_LIMIT);
        $today = Carbon::now()->format('Y-m-d');

        return new Digest(
            $attending,
            $this->scheduledSeries($user, fn (Series $series) => $series->nextOccurrenceDate()?->format('Y-m-d') === $today),
            $this->interests(
                $user,
                $attending,
                fn (Entity $entity) => $entity->todaysEvents($user),
                fn (Tag $tag) => $tag->todaysEvents($user),
            ),
        );
    }

    /**
     * The week ahead: events the user is going to in the next two weeks,
     * upcoming events from what they follow, and followed series that next
     * occur in the coming week. Without that date check any followed active
     * series made the weekly email go out every week (#2083).
     */
    public function weekly(User $user): Digest
    {
        $attending = $user->getAttendingFuture()->where('start_at', '<=', Carbon::now()->addDays(self::WEEKLY_ATTENDING_DAYS));
        // nextOccurrenceDate() is never before today in New York, so this is today through day 7
        $weekEnd = Carbon::now('America/New_York')->startOfDay()->addDays(self::WEEKLY_SERIES_DAYS);

        return new Digest(
            $attending,
            $this->scheduledSeries($user, fn (Series $series) => $series->nextOccurrenceDate()?->lt($weekEnd) ?? false),
            $this->interests(
                $user,
                $attending,
                fn (Entity $entity) => $entity->futureEvents(null, $user)->items(),
                fn (Tag $tag) => $tag->futureEvents($user),
            ),
        );
    }

    /**
     * Events from each followed entity, then each followed tag, by its name.
     * An event is listed once: not if the user is already going, nor again
     * under a later entity or tag.
     *
     * @param Collection<int, Event> $attending
     * @param callable(Entity): iterable<Event> $entityEvents
     * @param callable(Tag): iterable<Event> $tagEvents
     *
     * @return array<string, array<int, Event>>
     */
    private function interests(User $user, Collection $attending, callable $entityEvents, callable $tagEvents): array
    {
        $listed = $attending->pluck('id')->all();
        $interests = [];

        foreach ([[$user->getEntitiesFollowing(), $entityEvents], [$user->getTagsFollowing(), $tagEvents]] as [$followed, $eventsFor]) {
            foreach ($followed as $item) {
                $events = [];
                foreach ($eventsFor($item) as $event) {
                    if (!in_array($event->id, $listed)) {
                        $events[] = $event;
                        $listed[] = $event->id;
                    }
                }

                if ($events !== []) {
                    $interests[$item->name] = $events;
                }
            }
        }

        return $interests;
    }

    /**
     * Followed series that are scheduled and not cancelled, optionally only
     * those $when accepts.
     *
     * @param (callable(Series): bool)|null $when
     *
     * @return array<int, Series>
     */
    private function scheduledSeries(User $user, ?callable $when = null): array
    {
        $list = [];
        foreach ($user->getSeriesFollowing() as $series) {
            // a followed series may since have been made private
            if (!$series->isVisibleTo($user)) {
                continue;
            }

            if ($series->occurrenceType->name !== 'No Schedule' && null === $series->cancelled_at && (!$when || $when($series))) {
                $list[] = $series;
            }
        }

        return $list;
    }
}
