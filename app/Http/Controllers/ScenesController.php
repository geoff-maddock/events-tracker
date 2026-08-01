<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Curated genre "scene" hub pages: /scenes/{slug}. Config-driven v1 for
 * issue #2002 §4 — no database model, no migration. Each scene groups a
 * handful of real tag slugs (config/scenes.php) into one editorial landing
 * page combining upcoming events, key entities, and active series, so it
 * can rank for genre-demand searches ("raves pittsburgh", "goth events")
 * that a bare tag listing doesn't.
 */
class ScenesController extends Controller
{
    /**
     * Cached stats TTL in seconds (matches EventTimeWindowStats).
     */
    protected const STATS_CACHE_TTL = 900;

    public function index(): View
    {
        $scenes = collect(config('scenes', []))->map(function (array $scene, string $slug) {
            $scene['slug'] = $slug;
            $scene['stats'] = $this->stats($slug, $scene['tags']);

            return $scene;
        })->values();

        return view('scenes.index-tw', [
            'scenes' => $scenes,
        ]);
    }

    public function show(string $slug): View
    {
        $scene = config("scenes.$slug");

        abort_unless($scene, 404);

        $tags = $scene['tags'];

        $events = Event::query()
            ->visible($this->user)
            ->future()
            ->whereHas('tags', function ($query) use ($tags) {
                $query->whereIn('tags.slug', $tags);
            })
            ->with(EventsController::cardEventEagerLoad($this->user))
            ->orderBy('start_at')
            ->limit(48)
            ->get();

        // "Key" = tagged for the scene AND actually active: ranked by public
        // event count over the trailing year plus anything upcoming, counting
        // both lineup (pivot) events and events hosted as the venue. Tagged
        // entities with no events in that window are dropped entirely.
        $since = Carbon::now('America/New_York')->subYear();

        $entities = Entity::query()
            ->active()
            ->whereHas('tags', function ($query) use ($tags) {
                $query->whereIn('tags.slug', $tags);
            })
            ->withCount([
                'events as recent_events_count' => function ($query) use ($since) {
                    $query->where('events.visibility_id', Visibility::VISIBILITY_PUBLIC)
                        ->where('events.start_at', '>=', $since);
                },
                'venueEvents as recent_venue_events_count' => function ($query) use ($since) {
                    $query->where('events.visibility_id', Visibility::VISIBILITY_PUBLIC)
                        ->where('events.start_at', '>=', $since);
                },
            ])
            ->with(['roles', 'photos'])
            ->orderByRaw('(recent_events_count + recent_venue_events_count) DESC')
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->filter(function (Entity $entity) {
                return ((int) $entity->getAttribute('recent_events_count') + (int) $entity->getAttribute('recent_venue_events_count')) > 0;
            })
            ->values();

        $series = Series::query()
            ->visible($this->user)
            ->whereNull('cancelled_at')
            ->whereHas('tags', function ($query) use ($tags) {
                $query->whereIn('tags.slug', $tags);
            })
            ->with(['visibility', 'venue.locations', 'tags', 'entities', 'occurrenceType', 'photos'])
            ->orderBy('name')
            ->limit(8)
            ->get();

        $stats = $this->stats($slug, $tags);

        return view('scenes.show-tw', [
            'slug' => $slug,
            'scene' => $scene,
            'events' => $events,
            'entities' => $entities,
            'series' => $series,
            'stats' => $stats,
        ]);
    }

    /**
     * Public-only upcoming event count + distinct venue count for a scene,
     * cached for 15 minutes so the value is safe to share across every
     * visitor regardless of who's signed in (mirrors EventTimeWindowStats).
     *
     * @param array<int, string> $tags
     * @return array{events: int, venues: int}
     */
    protected function stats(string $slug, array $tags): array
    {
        return Cache::remember('scene-stats:'.$slug, self::STATS_CACHE_TTL, function () use ($tags) {
            $counts = Event::query()
                ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
                ->future()
                ->whereHas('tags', function ($query) use ($tags) {
                    $query->whereIn('tags.slug', $tags);
                })
                ->selectRaw('COUNT(*) as event_count, COUNT(DISTINCT venue_id) as venue_count')
                ->toBase()
                ->first();

            return [
                'events' => $counts ? (int) $counts->event_count : 0,
                'venues' => $counts ? (int) $counts->venue_count : 0,
            ];
        });
    }

}
