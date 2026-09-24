<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventReview;
use App\Models\ReviewType;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventReviewRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventReviewsController extends Controller
{
    protected array $rules = [
        'review' => ['required', 'min:3'],
    ];

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Event 		$event
     */
    public function index(Event $event): RedirectResponse
    {
        // Event reviews are shown inline on the event page; the standalone
        // listing view was retired with the Form:: helpers.
        return redirect()->route('events.show', $event->id);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Event 		$event
     */
    public function create(Event $event): View
    {
        return view('reviews.create-tw', compact('event'))
            ->with($this->getFormOptions());
    }

    protected function getFormOptions(): array
    {
        return [
            'reviewTypeOptions' => ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all()
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(EventReviewRequest $request, Event $event): RedirectResponse
    {
        $msg = '';

        // get the request
        $input = $request->all();
        $input['event_id'] = $event->id;
        $input['user_id'] = $this->user->id;
        if (isset($input['attended'])) {
            $input['attended'] = $input['attended'] == 'on' ? 1 : 0;
        } else {
            $input['attended'] = 0;
        }
        if (isset($input['confirmed'])) {
            $input['confirmed'] = $input['confirmed'] == 'on' ? 1 : 0;
        } else {
            $input['confirmed'] = 0;
        }

        $this->validate($request, $this->rules);

        $eventReview = EventReview::create($input);

        flash()->success('Success', 'Your review has been created');

        return redirect()->route('events.show', $event->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  Event 		$event
     * @param  EventReview  	$eventReview
     */
    public function show(Event $event, EventReview $eventReview): View
    {
        return view('events.show-tw', compact('event', 'eventReview'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Event 		$event
     * @param  EventReview  	$review
     */
    public function edit(Event $event, EventReview $review): View
    {
        $this->authorizeReviewChange($review);

        $reviewTypeOptions = ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('reviews.edit-tw', compact('event', 'review', 'reviewTypeOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EventReviewRequest $request, Event $event, EventReview $review): RedirectResponse
    {
        $this->authorizeReviewChange($review);

        // the event and author never change on edit
        $input = $request->only(['review_type_id', 'expectation', 'rating', 'review']);
        $input['attended'] = $request->input('attended') == 'on' ? 1 : 0;
        $input['confirmed'] = $request->input('confirmed') == 'on' ? 1 : 0;

        $review->fill($input)->save();

        flash()->success('Success', 'Your review has been updated!');

        return redirect()->route('events.show', ['event' => $review->event_id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * The argument must be named $review to bind the {review} route parameter;
     * as $eventReview it resolved to an empty model and the delete was a no-op.
     *
     * @param  Event $event
     * @param  EventReview $review
     * @throws \Exception
     */
    public function destroy(Event $event, EventReview $review): RedirectResponse
    {
        $this->authorizeReviewChange($review);

        $review->delete();

        \Session::flash('flash_message', 'Your review has been deleted!');

        return redirect()->route('events.show', $event->id);
    }

    /**
     * Only the review's author or an admin may change it.
     */
    private function authorizeReviewChange(EventReview $review): void
    {
        abort_unless($this->user && ((int) $review->user_id === $this->user->id || $this->user->isAdmin()), 403);
    }
}
