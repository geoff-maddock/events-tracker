<?php

namespace App\Services;

use App\Enums\EventTimeWindow;
use App\Models\Event;
use App\Models\Visibility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Live counts backing the /events/tonight, /today, /this-weekend,
 * /this-week landing pages: event count, distinct venue count, and the
 * top tags for the window. Counts are computed against public events only
 * so the cached value is safe to share across all visitors regardless of
 * who's signed in.
 */
class EventTimeWindowStats
{
    protected const CACHE_TTL = 900;

    /**
     * @return array{events: int, venues: int, tags: string[]}
     */
    public function stats(EventTimeWindow $window): array
    {
        $range = $window->range();

        $cacheKey = sprintf(
            'event-window-stats:%s:%s',
            $window->value,
            substr($range['start'], 0, 10)
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range): array {
            $counts = Event::query()
                ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
                ->between($range['start'], $range['end'])
                ->selectRaw('COUNT(*) as event_count, COUNT(DISTINCT venue_id) as venue_count')
                ->toBase()
                ->first();

            $tags = DB::table('event_tag')
                ->join('events', 'events.id', '=', 'event_tag.event_id')
                ->join('tags', 'tags.id', '=', 'event_tag.tag_id')
                ->where('events.visibility_id', Visibility::VISIBILITY_PUBLIC)
                ->whereBetween('events.start_at', [$range['start'], $range['end']])
                ->select('tags.name')
                ->selectRaw('COUNT(*) as frequency')
                ->groupBy('tags.name')
                ->orderByDesc('frequency')
                ->limit(3)
                ->pluck('tags.name')
                ->all();

            return [
                'events' => $counts ? (int) $counts->event_count : 0,
                'venues' => $counts ? (int) $counts->venue_count : 0,
                'tags' => $tags,
            ];
        });
    }
}
