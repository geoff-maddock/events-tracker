<?php

namespace App\Services;

use App\Enums\EventTimeWindow;
use App\Models\Event;
use App\Models\Visibility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Live counts backing the /events/tonight, /this-weekend and /this-week
 * landing pages: event count, distinct venue count, and the
 * top tags for the window. Counts are computed against public events only
 * so the cached value is safe to share across all visitors regardless of
 * who's signed in.
 */
class EventTimeWindowStats
{
    protected const CACHE_TTL = 900;

    /**
     * How many tags tagLinks carries. The page shows the first
     * TAG_LINKS_VISIBLE and folds the rest behind a "…" toggle.
     */
    public const TAG_LINKS_LIMIT = 24;

    public const TAG_LINKS_VISIBLE = 8;

    /**
     * tagLinks: the window's tags by frequency, for the drill-down pills.
     * tags: the top 3 names, for the meta description.
     *
     * @return array{events: int, venues: int, tags: string[], tagLinks: array<int, array{name: string, slug: string, count: int}>}
     */
    public function stats(EventTimeWindow $window): array
    {
        $cacheKey = $this->cacheKey($window);
        $range = $window->range();

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range): array {
            $counts = Event::query()
                ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
                ->between($range['start'], $range['end'])
                ->selectRaw('COUNT(*) as event_count, COUNT(DISTINCT venue_id) as venue_count')
                ->toBase()
                ->first();

            $tagLinks = DB::table('event_tag')
                ->join('events', 'events.id', '=', 'event_tag.event_id')
                ->join('tags', 'tags.id', '=', 'event_tag.tag_id')
                ->where('events.visibility_id', Visibility::VISIBILITY_PUBLIC)
                ->whereBetween('events.start_at', [$range['start'], $range['end']])
                ->select('tags.name', 'tags.slug')
                ->selectRaw('COUNT(*) as frequency')
                ->groupBy('tags.name', 'tags.slug')
                ->orderByDesc('frequency')
                ->orderBy('tags.name')
                ->limit(self::TAG_LINKS_LIMIT)
                ->get()
                ->map(fn ($row) => [
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'count' => (int) $row->frequency,
                ])
                ->all();

            return [
                'events' => $counts ? (int) $counts->event_count : 0,
                'venues' => $counts ? (int) $counts->venue_count : 0,
                'tags' => array_column(array_slice($tagLinks, 0, 3), 'name'),
                'tagLinks' => $tagLinks,
            ];
        });
    }

    /**
     * Versioned so a deploy that changes the payload shape never serves a
     * stale entry missing the new keys.
     */
    protected function cacheKey(EventTimeWindow $window): string
    {
        $range = $window->range();

        return sprintf(
            'event-window-stats:v2:%s:%s',
            $window->value,
            substr($range['start'], 0, 10)
        );
    }
}
