<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Services\Embeds\OembedExtractor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The four routes that load the audio embeds of an event, entity or series
 * after its page or card has rendered (#2406): an HTML fragment by id for the
 * page's own ajax call, and JSON by slug, each in a full form and a minimal
 * one (the first embed only, in the small player).
 *
 * The controller says which model, and what to eager load, in embedQuery().
 */
trait LoadsEmbeds
{
    /**
     * A query on the model these routes serve (Event, Entity or Series), with
     * the relations its embeds are read from.
     *
     * @return Builder<Event>|Builder<Entity>|Builder<Series>
     */
    abstract protected function embedQuery(): Builder;

    /**
     * @throws \Throwable
     */
    public function loadEmbeds(int $id, OembedExtractor $embedExtractor, Request $request): RedirectResponse | array
    {
        return $this->embedFragment($request, $embedExtractor, $this->embedSubject('id', $id), minimal: false);
    }

    /**
     * @throws \Throwable
     */
    public function loadMinimalEmbeds(int $id, OembedExtractor $embedExtractor, Request $request): RedirectResponse | array
    {
        return $this->embedFragment($request, $embedExtractor, $this->embedSubject('id', $id), minimal: true);
    }

    /**
     * No sign-in required.
     */
    public function loadEmbedsBySlug(string $slug, OembedExtractor $embedExtractor, Request $request): JsonResponse | RedirectResponse
    {
        return $this->embedJson($request, $embedExtractor, $this->embedSubject('slug', $slug), minimal: false);
    }

    /**
     * No sign-in required.
     */
    public function loadMinimalEmbedsBySlug(string $slug, OembedExtractor $embedExtractor, Request $request): JsonResponse | RedirectResponse
    {
        return $this->embedJson($request, $embedExtractor, $this->embedSubject('slug', $slug), minimal: true);
    }

    /**
     * The embeds rendered as the playlist the page inserts.
     *
     * @return RedirectResponse|array{Message: string, Success: string}
     */
    private function embedFragment(Request $request, OembedExtractor $embedExtractor, Event|Entity|Series|null $subject, bool $minimal): RedirectResponse | array
    {
        if ($subject === null) {
            flash()->error('Error', 'No such '.$this->embedNoun());

            return back();
        }

        if (!$request->ajax()) {
            return $this->embedsNotAPage();
        }

        $embeds = $this->embedsOf($subject, $embedExtractor, $minimal);

        return [
            'Message' => 'Added embeds to '.$this->embedNoun().' page.',
            'Success' => view($minimal ? 'embeds.minimal-playlist' : 'embeds.playlist')
                ->with(compact('embeds'))
                ->render(),
        ];
    }

    private function embedJson(Request $request, OembedExtractor $embedExtractor, Event|Entity|Series|null $subject, bool $minimal): JsonResponse | RedirectResponse
    {
        $wantsJson = $request->ajax() || $request->wantsJson();

        if ($subject === null) {
            if ($wantsJson) {
                return response()->json(['error' => ucfirst($this->embedNoun()).' not found'], 404);
            }
            flash()->error('Error', 'No such '.$this->embedNoun());

            return back();
        }

        if (!$wantsJson) {
            return $this->embedsNotAPage();
        }

        return response()->json([
            'data' => $this->embedsOf($subject, $embedExtractor, $minimal),
            'message' => 'Embeds loaded successfully',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function embedsOf(Event|Entity|Series $subject, OembedExtractor $embedExtractor, bool $minimal): array
    {
        $embedExtractor->setLayout($minimal ? 'small' : 'medium');

        $embeds = match (true) {
            $subject instanceof Event => $embedExtractor->getEmbedsForEvent($subject),
            $subject instanceof Series => $embedExtractor->getEmbedsForSeries($subject),
            default => $embedExtractor->getEmbedsForEntity($subject),
        };

        return $minimal ? array_slice($embeds, 0, 1) : $embeds;
    }

    /**
     * The record with $column (id or slug) equal to $value, or null when
     * there is none the viewer may see.
     */
    private function embedSubject(string $column, int|string $value): Event|Entity|Series|null
    {
        /** @var Event|Entity|Series|null $subject */
        $subject = $this->embedQuery()->where($column, $value)->first();

        // an entity the viewer may not see is already left out by its global scope
        if ($subject === null || $subject instanceof Entity) {
            return $subject;
        }

        return $subject->isVisibleTo($this->user) ? $subject : null;
    }

    /**
     * These routes are not pages: opened directly, they send the visitor back.
     */
    private function embedsNotAPage(): RedirectResponse
    {
        flash()->success('Error', 'You cannot load embeds directly');

        return back();
    }

    /**
     * What the messages call the record.
     */
    private function embedNoun(): string
    {
        return match (true) {
            $this->embedQuery()->getModel() instanceof Event => 'event',
            $this->embedQuery()->getModel() instanceof Series => 'series',
            default => 'entity',
        };
    }
}
