<?php

namespace App\Http\Controllers;

use App\Filters\EventFilters;
use App\Models\Entity;
use App\Services\Player\PlayerQueueBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * JSON endpoints behind the site-wide audio player (#2366).
 */
class PlayerController extends Controller
{
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
     * filters[...] as event lists), radar, or one event/series/entity.
     */
    public function queue(Request $request, PlayerQueueBuilder $builder, EventFilters $eventFilters): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['nullable', 'string', 'in:upcoming,radar,event,series,entity'],
            'id' => ['required_if:source,event,series', 'integer'],
            'slug' => ['required_if:source,entity', 'string', 'max:255'],
            'filters' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $source = $validated['source'] ?? 'upcoming';

        switch ($source) {
            case 'radar':
                if (!$user) {
                    return response()->json(['message' => 'Sign in to play your radar.'], 401);
                }
                $items = $builder->radar($user);
                break;
            case 'event':
                $items = $builder->event((int) $validated['id'], $user);
                break;
            case 'series':
                $items = $builder->series((int) $validated['id'], $user);
                break;
            case 'entity':
                $entity = Entity::where('slug', $validated['slug'])->first();
                $items = $entity ? $builder->entity($entity, $user) : null;
                break;
            default:
                $items = $builder->upcoming($user, $eventFilters, $validated['filters'] ?? []);
        }

        if ($items === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json([
            'source' => $source,
            'items' => $items,
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
