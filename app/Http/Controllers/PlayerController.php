<?php

namespace App\Http\Controllers;

use App\Filters\EntityFilters;
use App\Filters\EventFilters;
use App\Models\Entity;
use App\Services\Player\PlayerQueueBuilder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * JSON endpoints behind the site-wide audio player (#2366).
 */
class PlayerController extends Controller
{
    public const QUEUE_CACHE_SECONDS = 600;

    /**
     * The pop-out player window. It reads its queue source from its own query
     * string (the same parameters queue() takes) and fetches everything else.
     */
    public function show(): View
    {
        return view('player.show-tw');
    }

    /**
     * The player queue for a source: upcoming (optionally filtered with the same
     * filters[...] as event lists), an entity list (entity list filters[...],
     * sort and direction), radar, attending, a venue, or one
     * event/series/entity, a page
     * of PlayerQueueBuilder::PAGE_SIZE items from `offset`.
     */
    public function queue(Request $request, PlayerQueueBuilder $builder, EventFilters $eventFilters, EntityFilters $entityFilters): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['nullable', 'string', 'in:upcoming,radar,attending,venue,event,series,entity,entities'],
            'id' => ['required_if:source,event,series', 'integer'],
            'slug' => ['required_if:source,entity,venue', 'string', 'max:255'],
            'filters' => ['nullable', 'array'],
            'sort' => ['nullable', 'string', 'max:64'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:'.PlayerQueueBuilder::MAX_ITEMS],
        ]);

        $user = $request->user();
        $source = $validated['source'] ?? 'upcoming';

        if (in_array($source, ['radar', 'attending'], true) && !$user) {
            return response()->json(['message' => 'Sign in to play your '.$source.'.'], 401);
        }

        // Built once and cached briefly, so paging through a queue doesn't rebuild it
        // for every page and offsets stay stable between pages. Keyed by viewer
        // (visibility differs per user) and day (upcoming/past flips at midnight).
        $key = 'player-queue:'.sha1((string) json_encode([
            $source,
            $validated['id'] ?? null,
            $validated['slug'] ?? null,
            in_array($source, ['upcoming', 'entities'], true) ? ($validated['filters'] ?? []) : [],
            $source === 'entities' ? [$validated['sort'] ?? null, $validated['direction'] ?? null] : null,
            $user?->id,
            Carbon::today()->toDateString(),
        ]));

        $items = Cache::remember($key, self::QUEUE_CACHE_SECONDS, function () use ($source, $validated, $user, $builder, $eventFilters, $entityFilters) {
            switch ($source) {
                // guests got a 401 above
                case 'radar':
                    return $user ? $builder->radar($user) : null;
                case 'attending':
                    return $user ? $builder->attending($user) : null;
                case 'venue':
                    return $builder->venue((string) $validated['slug'], $user);
                case 'event':
                    return $builder->event((int) $validated['id'], $user);
                case 'series':
                    return $builder->series((int) $validated['id'], $user);
                case 'entities':
                    return $builder->entities($user, $entityFilters, $validated['filters'] ?? [], $validated['sort'] ?? null, $validated['direction'] ?? null);
                case 'entity':
                    $entity = Entity::where('slug', $validated['slug'])->first();

                    return $entity ? $builder->entity($entity, $user) : null;
                default:
                    return $builder->upcoming($user, $eventFilters, $validated['filters'] ?? []);
            }
        });

        if ($items === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // one page at a time: the player asks for the next when the listener
        // scrolls to the end of the list or plays past the last item
        $offset = (int) ($validated['offset'] ?? 0);
        $nextOffset = $offset + PlayerQueueBuilder::PAGE_SIZE;

        return response()->json([
            'source' => $source,
            'items' => $builder->finish(array_slice($items, $offset, PlayerQueueBuilder::PAGE_SIZE), $user),
            'offset' => $offset,
            'next_offset' => $nextOffset < count($items) ? $nextOffset : null,
            'total' => count($items),
        ]);
    }

    /**
     * Playable sources for one entity's SoundCloud/Bandcamp links, resolved and cached per link.
     */
    public function sources(Entity $entity, PlayerQueueBuilder $builder): JsonResponse
    {
        return response()->json([
            'entity' => ['id' => $entity->id, 'slug' => $entity->slug],
            'sources' => $builder->sourcesFor($entity),
        ]);
    }
}
