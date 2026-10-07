<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The site's popularity ranking, shared by the onboarding picker and the
 * weekly digest's Essential Events fallback (#2102), so there is one
 * definition of "popular" rather than one per caller.
 */
class PopularContent
{
    /**
     * Upcoming, uncancelled events the viewer can see, most attendees first.
     *
     * Not via Event::future(): that scope adds orderBy('start_at') first, so
     * an attendee ordering after it only broke ties between same-time events.
     *
     * @return Collection<int, Event>
     */
    public function events(?User $viewer, int $limit, ?Carbon $before = null): Collection
    {
        return Event::query()
            ->where('start_at', '>=', Carbon::today()->startOfDay())
            ->when($before, fn ($q) => $q->where('start_at', '<', $before))
            ->whereNull('cancelled_at')
            ->visible($viewer)
            ->withCount('attendees')
            ->with(['venue', 'eventType', 'series'])
            ->orderByDesc('attendees_count')
            ->orderBy('start_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Active entities by follows plus events.
     *
     * @return Collection<int, Entity>
     */
    public function entities(int $limit): Collection
    {
        return Entity::query()
            ->active()
            ->withCount(['follows', 'events'])
            ->with(['photos', 'entityType'])
            ->orderByDesc(DB::raw('follows_count + events_count'))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Tags by events plus follows.
     *
     * @return Collection<int, Tag>
     */
    public function tags(int $limit): Collection
    {
        return Tag::query()
            ->withCount(['events', 'follows'])
            ->orderByDesc(DB::raw('events_count + follows_count'))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
