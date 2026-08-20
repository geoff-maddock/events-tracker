<?php

namespace App\Http\Controllers;

use App\Enums\EventTimeWindow;
use App\Models\Event;
use App\Models\Tag;
use App\Services\EventTimeWindowStats;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Stable, crawlable time-window landing pages: /events/tonight,
 * /events/this-weekend, /events/this-week. An optional ?tag={slug} narrows
 * the window to one tag (the drill-down pills); those variants are
 * noindexed via SeoMeta::NOINDEX_PARAMS and canonicalize to the clean path.
 *
 * Deliberately does NOT use ListParameterSessionStore / ListEntityResultBuilder
 * — these pages must render the same content for every visitor and must not
 * inherit session filter/sort/tab state from the main /events listing.
 */
class EventTimeWindowController extends Controller
{
    public function show(Request $request, string $window, EventTimeWindowStats $stats): View
    {
        $timeWindow = EventTimeWindow::tryFrom($window) ?? abort(404);

        $range = $timeWindow->range();

        $activeTag = $this->activeTag($request);

        $events = Event::query()
            ->visible($this->user)
            ->between($range['start'], $range['end'])
            ->when($activeTag, function ($query) use ($activeTag) {
                $query->whereHas('tags', function ($q) use ($activeTag) {
                    $q->where('tags.id', $activeTag->id);
                });
            })
            ->with(EventsController::cardEventEagerLoad($this->user))
            ->paginate(48);

        if ($activeTag) {
            $events->appends(['tag' => $activeTag->slug]);
        }

        return view('events.time-window-tw', [
            'window' => $timeWindow,
            'events' => $events,
            'stats' => $stats->stats($timeWindow),
            'activeTag' => $activeTag,
        ]);
    }

    protected function activeTag(Request $request): ?Tag
    {
        $slug = $request->query('tag');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return Tag::where('slug', $slug)->first() ?? abort(404);
    }
}
