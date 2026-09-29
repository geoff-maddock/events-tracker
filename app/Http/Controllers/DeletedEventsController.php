<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin view of soft-deleted events, with restore (#2192). Deleted events are
 * kept indefinitely; restoring one brings back its RSVPs, reviews and links,
 * which a soft delete leaves in place.
 */
class DeletedEventsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);

        parent::__construct();
    }

    public function index(): View
    {
        $events = Event::onlyTrashed()
            ->with(['venue', 'user'])
            ->withCount('eventResponses')
            ->orderByDesc('deleted_at')
            ->paginate(50);

        // who deleted each event, from the activity log (action 3 = delete)
        $deletedBy = Activity::with('user')
            ->where('object_table', 'Event')
            ->where('action_id', Action::DELETE)
            ->whereIn('object_id', $events->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('object_id')
            ->keyBy('object_id');

        return view('events.deleted-tw', compact('events', 'deletedBy'));
    }

    public function restore(int $id): RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($id);
        $event->restore();

        // logged as an update, with a message saying it was a restore
        Activity::log($event, $this->user, Action::UPDATE, 'Restored a deleted event');

        flash()->success('Restored', sprintf('"%s" has been restored.', $event->name));

        return redirect()->route('events.deleted');
    }
}
