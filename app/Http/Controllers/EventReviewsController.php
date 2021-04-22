<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventReview;
use App\Models\ReviewType;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventReviewRequest;
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
        return view('reviews.create', compact('event'))
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
     * @return \Illuminate\Http\Response
     */
    public function store(EventReviewRequest $request, Event $event)
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

        $input = $request->input();
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

        $eventReview->fill($input)->save();

        flash()->success('Success', 'Your review has been updated!');

        return redirect()->route('events.show', ['event' => $event->id]);
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
