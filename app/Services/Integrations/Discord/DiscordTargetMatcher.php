<?php

namespace App\Services\Integrations\Discord;

use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use App\Models\Event;
use App\Models\Visibility;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Decides which events reach which Discord channels (issue #2058).
 *
 * SINGLE SOURCE OF TRUTH. Both directions of the question — "which targets
 * want this event?" and "which events does this target want?" — are answered
 * by the same SQL through applyCriteria(). A hand-written PHP predicate
 * alongside the query builder would be faster per call and would drift within
 * a release; that exact drift already exists in this codebase between
 * AutomateInstagramPosts::getEventsToPost() and InstagramEventPoster. With a
 * few dozen targets, one indexed EXISTS query per target is not worth the bug.
 *
 * See DiscordTarget for the include/exclude semantics these criteria combine
 * under.
 */
class DiscordTargetMatcher
{
    /**
     * Enabled targets opted into $mode whose filters this event satisfies.
     *
     * @return Collection<int, DiscordTarget>
     */
    public function targetsFor(Event $event, string $mode): Collection
    {
        /** @var Collection<int, DiscordTarget> $candidates */
        $candidates = DiscordTarget::forMode($mode)->with('criteria')->get();

        return $candidates->filter(
            fn (DiscordTarget $target): bool => $this->accepts($target, $event)
        )->values();
    }

    /**
     * Whether a specific target would accept a specific event, asked of the
     * database so it cannot disagree with eventsFor().
     */
    public function accepts(DiscordTarget $target, Event $event): bool
    {
        return $this->applyCriteria($this->baseEligible($target), $target)
            ->whereKey($event->getKey())
            ->exists();
    }

    /**
     * The events a target wants, optionally windowed by start time. Used by
     * the reminder and digest commands, and by the admin preview.
     *
     * @return Builder<Event>
     */
    public function eventsFor(
        DiscordTarget $target,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): Builder {
        $query = $this->applyCriteria($this->baseEligible($target), $target);

        if (null !== $from) {
            $query->where('start_at', '>=', $from);
        }

        if (null !== $to) {
            $query->where('start_at', '<=', $to);
        }

        return $query->orderBy('start_at');
    }

    /**
     * The gate every mode shares, regardless of criteria: a public, future,
     * live event its owner has not opted out of reposting.
     *
     * @return Builder<Event>
     */
    private function baseEligible(DiscordTarget $target): Builder
    {
        $query = Event::query()
            ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            ->where('start_at', '>=', now())
            // do_not_repost is shared with the Instagram integration: opting an
            // event out of reposting opts it out everywhere.
            ->where(function (Builder $q): void {
                $q->where('do_not_repost', false)->orWhereNull('do_not_repost');
            })
            ->whereHas('eventType');

        if ($target->requires_photo) {
            $query->whereHas('photos', function ($q): void {
                $q->where('photos.is_primary', true);
            });
        }

        return $query;
    }

    /**
     * Apply a target's include/exclude criteria to an event query.
     *
     * Within a criteria type includes are OR'd; across types they are AND'd;
     * any exclude match vetoes. A target with no includes and match_all unset
     * matches nothing — a half-configured channel should go quiet rather than
     * receive every event on the site.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function applyCriteria(Builder $query, DiscordTarget $target): Builder
    {
        $criteria = $target->relationLoaded('criteria')
            ? $target->criteria
            : $target->criteria()->get();

        /** @var Collection<int, DiscordTargetCriterion> $criteria */
        $includes = $criteria->where('mode', DiscordTargetCriterion::MODE_INCLUDE)
            ->groupBy('criteria_type');
        $excludes = $criteria->where('mode', DiscordTargetCriterion::MODE_EXCLUDE)
            ->groupBy('criteria_type');

        if ($includes->isEmpty() && ! $target->match_all) {
            return $query->whereRaw('1 = 0');
        }

        foreach ($includes as $type => $group) {
            $ids = $group->pluck('criteria_id')->map('intval')->all();
            $this->constrain($query, (string) $type, $ids, exclude: false);
        }

        foreach ($excludes as $type => $group) {
            $ids = $group->pluck('criteria_id')->map('intval')->all();
            $this->constrain($query, (string) $type, $ids, exclude: true);
        }

        return $query;
    }

    /**
     * One criteria type against an event query.
     *
     * @param  Builder<Event>  $query
     * @param  array<int, int>  $ids
     */
    private function constrain(Builder $query, string $type, array $ids, bool $exclude): void
    {
        if ([] === $ids) {
            return;
        }

        match ($type) {
            DiscordTargetCriterion::TYPE_TAG => $this->constrainRelation($query, 'tags', 'tags.id', $ids, $exclude),
            DiscordTargetCriterion::TYPE_ENTITY => $this->constrainRelation($query, 'entities', 'entities.id', $ids, $exclude),
            DiscordTargetCriterion::TYPE_VENUE => $this->constrainColumn($query, 'venue_id', $ids, $exclude),
            DiscordTargetCriterion::TYPE_PROMOTER => $this->constrainColumn($query, 'promoter_id', $ids, $exclude),
            DiscordTargetCriterion::TYPE_EVENT_TYPE => $this->constrainColumn($query, 'event_type_id', $ids, $exclude),
            DiscordTargetCriterion::TYPE_SERIES => $this->constrainColumn($query, 'series_id', $ids, $exclude),
            // An unrecognised type must not silently widen the match.
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @param  Builder<Event>  $query
     * @param  array<int, int>  $ids
     */
    private function constrainRelation(Builder $query, string $relation, string $column, array $ids, bool $exclude): void
    {
        $constraint = function ($q) use ($column, $ids): void {
            $q->whereIn($column, $ids);
        };

        $exclude
            ? $query->whereDoesntHave($relation, $constraint)
            : $query->whereHas($relation, $constraint);
    }

    /**
     * @param  Builder<Event>  $query
     * @param  array<int, int>  $ids
     */
    private function constrainColumn(Builder $query, string $column, array $ids, bool $exclude): void
    {
        if ($exclude) {
            // A null column is not excluded — an event with no venue is not
            // "at the venue we are filtering out".
            $query->where(function (Builder $q) use ($column, $ids): void {
                $q->whereNotIn($column, $ids)->orWhereNull($column);
            });

            return;
        }

        $query->whereIn($column, $ids);
    }
}
