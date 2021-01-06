<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventReview;
use App\Models\ReviewType;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EventReviewsController extends Controller
{
    protected $rules = [
        'review' => ['required', 'min:3'],
    ];

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Event 		$event
     * @return Response
     */
    public function index(Event $event)
    {
        return view('reviews.index', compact('event'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Event 		$event
     * @return Response
     */
    public function create(Event $event)
    {
        $reviewTypes = ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('reviews.create', compact('event', 'reviewTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request 			$request
     * @param  Event 		$event
     * @return Response
     */
    public function store(Request $request, Event $event)
    {
        $msg = '';

        // get the request
        $input = $request->all();
        $input['event_id'] = $event->id;
        $input['user_id'] = $this->user->id;

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
     * @return Response
     */
    public function show(Event $event, EventReview $eventReview)
    {
        return view('events.show', compact('event', 'eventReview'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Event 		$event
     * @param  EventReview  	$eventReview
     * @return Response
     */
    public function edit(Event $event, EventReview $eventReview)
    {
        $reviewTypes = ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('reviews.edit', compact('event', 'eventReview', 'reviewTypes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request 			$request
     * @param  Event 		$event
     * @param  EventReview  	$eventReview
     * @return Response
     */
    public function update(Request $request, Event $event, EventReview $eventReview)
    {
        $msg = '';

        $eventReview->fill($request->input())->save();

        flash()->success('Success', 'Your eventReview has been updated!');

        return redirect()->route('entities.show', $event->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Event $event
     * @param  EventReview $eventReview
     * @return Response
     * @throws \Exception
     */
    public function destroy(Event $event, EventReview $eventReview)
    {
        $eventReview->delete();

        \Session::flash('flash_message', 'Your review has been deleted!');

        return redirect()->route('entities.show', $event->id);
    }
}
