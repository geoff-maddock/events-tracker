<?php

namespace App\Http\Controllers;

use App\Enums\EventTimeWindow;
use App\Models\Event;
use App\Services\EventTimeWindowStats;
use Illuminate\Contracts\View\View;

/**
 * Stable, crawlable time-window landing pages: /events/tonight,
 * /events/this-weekend, /events/this-week.
 *
 * Deliberately does NOT use ListParameterSessionStore / ListEntityResultBuilder
 * — these pages must render the same content for every visitor and must not
 * inherit session filter/sort/tab state from the main /events listing.
 */
class EventTimeWindowController extends Controller
{
    public function show(string $window, EventTimeWindowStats $stats): View
    {
        $timeWindow = EventTimeWindow::tryFrom($window) ?? abort(404);

        $range = $timeWindow->range();

        $events = Event::query()
            ->visible($this->user)
            ->between($range['start'], $range['end'])
            ->with(EventsController::cardEventEagerLoad($this->user))
            ->paginate(48);

        return view('events.time-window-tw', [
            'window' => $timeWindow,
            'events' => $events,
            'stats' => $stats->stats($timeWindow),
        ]);
    }
}
