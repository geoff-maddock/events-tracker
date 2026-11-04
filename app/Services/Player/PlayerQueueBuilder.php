<?php

namespace App\Services\Player;

use App\Filters\EventFilters;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Link;
use App\Models\Series;
use App\Models\User;
use App\Services\Embeds\OembedExtractor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Builds the audio player's queue (#2366): the artists on a set of events who
 * have a SoundCloud or Bandcamp link, one item per entity, in event order.
 *
 * Only entity metadata is returned here. Each item's playable sources are
 * resolved separately (and lazily) through OembedExtractor::getSourcesForUrl(),
 * because a Bandcamp artist page can take several fetches to resolve.
 */
class PlayerQueueBuilder
{
    public const MAX_ITEMS = 50;

    // events scanned for a queue; enough to fill MAX_ITEMS on a busy listing
    public const MAX_EVENTS = 300;

    public function __construct(protected OembedExtractor $extractor)
    {
    }

    /**
     * Events visible to $user. Unfiltered, that's upcoming shows only. With
     * event-list filters (a tag page, a filtered list) it mirrors the list,
     * which includes past events: artists on upcoming matching events first
     * (soonest first), then artists from past ones (most recent first).
     *
     * @param array<array-key, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(?User $user, EventFilters $eventFilters, array $filters = []): array
    {
        $filters = $eventFilters->normalizeFilters($filters);
        $items = [];
        $seen = [];

        $upcoming = Event::query()->visible($user)->where('events.start_at', '>=', Carbon::today()->startOfDay());
        $this->collect($filters === [] ? $upcoming : $eventFilters->applyFilters($upcoming, $filters), $items, $seen);

        if ($filters !== []) {
            $past = Event::query()->visible($user)->where('events.start_at', '<', Carbon::today()->startOfDay());
            $this->collect($eventFilters->applyFilters($past, $filters), $items, $seen, 'desc');
        }

        return $this->finish($items, $user);
    }

    /**
     * The user's radar, in the radar page's order: the lineups of upcoming
     * events they're attending first, then events matching what they follow.
     * Each group is in date order; an artist in both stays in the first.
     *
     * @return list<array<string, mixed>>
     */
    public function radar(User $user): array
    {
        $attendingIds = $user->getAttendingFuture()->pluck('id');
        $recommendedIds = $user->getRecommendedEvents()->reorder()->select('events.id');

        $items = [];
        $seen = [];

        $this->collect(Event::query()->visible($user)->future()->whereIn('events.id', $attendingIds), $items, $seen);
        $this->collect(
            Event::query()->visible($user)->future()
                ->whereIn('events.id', $recommendedIds)
                ->whereNotIn('events.id', $attendingIds),
            $items,
            $seen
        );

        return $this->finish($items, $user);
    }

    /**
     * One event's lineup, past or future, if $user can see the event.
     *
     * @return list<array<string, mixed>>|null null when the event isn't visible
     */
    public function event(int $id, ?User $user): ?array
    {
        $query = Event::query()->visible($user)->whereKey($id);

        if (!$query->exists()) {
            return null;
        }

        return $this->fromEvents($query, $user);
    }

    /**
     * A series' own entities, then the lineups of its upcoming visible events.
     *
     * @return list<array<string, mixed>>|null null when the series isn't visible
     */
    public function series(int $id, ?User $user): ?array
    {
        $series = Series::query()->visible($user)->whereKey($id)
            ->with(['entities' => fn (Relation $q) => $this->withAudioLinks($q)])
            ->first();

        if (!$series) {
            return null;
        }

        $items = [];
        $seen = [];

        foreach ($series->entities as $entity) {
            $this->push($items, $seen, $entity, null);
        }

        $events = Event::query()->visible($user)->future()->where('events.series_id', $series->id);

        $this->collect($events, $items, $seen);

        return $this->finish($items, $user);
    }

    /**
     * A single entity, with its next upcoming visible event as context.
     *
     * @return list<array<string, mixed>>
     */
    public function entity(Entity $entity, ?User $user): array
    {
        $entity->loadMissing(['links', 'roles']);

        $nextEvent = $entity->events()->getQuery()
            ->visible($user)->future()
            ->first();

        $items = [];
        $seen = [];
        $this->push($items, $seen, $entity, $nextEvent, false);

        return $this->finish($items, $user);
    }

    /**
     * Playable sources for every audio link on $entity: SoundCloud first, since
     * the player can start, seek and auto-advance those, while Bandcamp's embed
     * only plays from its own button. Link order within each provider.
     *
     * @return list<array{provider: string, url: string, src: string, title: ?string}>
     */
    public function sourcesFor(Entity $entity): array
    {
        $sources = [];
        $seen = [];

        foreach ($this->audioLinks($entity) as $link) {
            foreach ($this->extractor->getSourcesForUrl((string) $link->url) as $source) {
                if (isset($seen[$source['src']])) {
                    continue;
                }
                $seen[$source['src']] = true;
                $sources[] = $source;
            }
        }

        // usort is stable, so link order holds within each provider
        usort($sources, fn (array $a, array $b) => ($a['provider'] !== 'soundcloud') <=> ($b['provider'] !== 'soundcloud'));

        return $sources;
    }

    /**
     * @param Builder<Event> $events
     *
     * @return list<array<string, mixed>>
     */
    protected function fromEvents(Builder $events, ?User $user): array
    {
        $items = [];
        $seen = [];

        $this->collect($events, $items, $seen);

        return $this->finish($items, $user);
    }

    /**
     * Append the audio entities of $events (by start time, $direction) to $items.
     *
     * @param Builder<Event> $events
     * @param list<array<string, mixed>> $items
     * @param array<int, true> $seen
     */
    protected function collect(Builder $events, array &$items, array &$seen, string $direction = 'asc'): void
    {
        if (count($items) >= self::MAX_ITEMS) {
            return;
        }

        $events = $events
            ->reorder('events.start_at', $direction)
            ->orderBy('events.id')
            ->with(['entities' => fn (Relation $q) => $this->withAudioLinks($q)])
            ->take(self::MAX_EVENTS)
            ->get();

        foreach ($events as $event) {
            foreach ($event->entities as $entity) {
                if (count($items) >= self::MAX_ITEMS) {
                    break 2;
                }
                $this->push($items, $seen, $entity, $event);
            }
        }
    }

    /**
     * Limit an entities relation to entities with an audio link, loading what push() reads.
     *
     * @param Relation<*, *, *> $query
     */
    protected function withAudioLinks(Relation $query): void
    {
        $query
            ->whereHas('links', fn (Builder $q) => $q->where(function (Builder $w) {
                $w->where('url', 'like', '%soundcloud.com%')->orWhere('url', 'like', '%bandcamp.com%');
            }))
            ->with(['links', 'roles'])
            ->orderBy('entities.name');
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<int, true> $seen
     */
    protected function push(array &$items, array &$seen, Entity $entity, ?Event $event, bool $skipVenues = true): void
    {
        if (isset($seen[$entity->id]) || $this->audioLinks($entity)->isEmpty()) {
            return;
        }

        // a venue's Bandcamp/SoundCloud isn't the act playing; keep venues that also perform
        if ($skipVenues && $entity->roles->isNotEmpty()
            && $entity->roles->every(fn ($role) => strcasecmp((string) $role->name, 'Venue') === 0)
        ) {
            return;
        }

        $seen[$entity->id] = true;

        $items[] = [
            'entity' => [
                'id' => $entity->id,
                'name' => $entity->name,
                'slug' => $entity->slug,
                'url' => route('entities.show', ['entity' => $entity->slug]),
                'followed' => false,
            ],
            'event' => $event ? [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'start_at' => $event->start_at?->toIso8601String(),
                'past' => $event->start_at !== null && $event->start_at->lt(Carbon::today()->startOfDay()),
                'url' => route('events.show', ['event' => $event->slug]),
            ] : null,
            'providers' => $this->audioLinks($entity)
                ->map(fn (Link $link) => OembedExtractor::soundcloudUrl((string) $link->url) !== null ? 'soundcloud' : 'bandcamp')
                ->unique()->sortBy(fn (string $provider) => $provider !== 'soundcloud')->values()->all(),
            'sources_url' => route('player.sources', ['entity' => $entity->slug]),
        ];
    }

    /**
     * Fill in whether $user follows each item's entity.
     *
     * @param list<array<string, mixed>> $items
     *
     * @return list<array<string, mixed>>
     */
    protected function finish(array $items, ?User $user): array
    {
        if (!$user || $items === []) {
            return $items;
        }

        $followed = Follow::query()
            ->where('user_id', $user->id)
            ->where('object_type', 'entity')
            ->whereIn('object_id', array_map(fn (array $item) => $item['entity']['id'], $items))
            ->pluck('object_id')
            ->flip();

        foreach ($items as $i => $item) {
            $items[$i]['entity']['followed'] = $followed->has($item['entity']['id']);
        }

        return $items;
    }

    /**
     * @return Collection<int, Link>
     */
    protected function audioLinks(Entity $entity): Collection
    {
        return $entity->links
            ->filter(fn (Link $link) => OembedExtractor::isAudioUrl((string) $link->url))
            ->sortBy('created_at')
            ->values();
    }
}
