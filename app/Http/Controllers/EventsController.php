<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Mail;
use App\Event;
use App\Entity;
use App\EventType;
use App\Series;
use App\EntityType;
use App\Role;
use App\Tag;
use App\Visibility;
use App\Photo;
use App\EventResponse;
use App\ResponseType;
use App\User;
use App\Activity;
use App\Services\RssFeed;

class EventsController extends Controller {


	public function __construct(Event $event)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->event = $event;

		$this->rpp = 5;
		parent::__construct();
	}
	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function index()
	{

		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();;

		$future_events = Event::future()->paginate($this->rpp);
		$future_events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		// ages filter
	/*	if ($request->has('ages'))
		{
			$future_events->filter(function($e) {
				return ($e->min_age == $request->ages);
			});
		};
	*/
		$past_events = Event::past()->paginate($this->rpp);
		$past_events->filter(function($e)
		{
			return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		return view('events.index', compact('future_events','past_events'));
	}

	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function indexAll()
	{
		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		$future_events = Event::future()->paginate(100000);
		$future_events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});


		$past_events = Event::past()->paginate(100000);
		$past_events->filter(function($e)
		{
			return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		return view('events.index', compact('future_events','past_events'));
	}

	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function indexFuture()
	{
		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		$this->rpp = 10000;

		$future_events = Event::future()->paginate($this->rpp);
		$future_events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		return view('events.index', compact('future_events'));
	}

	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function indexPast()
	{
		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		$this->rpp = 10;
		
		$past_events = Event::past()->paginate($this->rpp);
		$past_events->filter(function($e)
		{
			return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		return view('events.index', compact('past_events'));
	}

	/**
 	 * Display a simple text feed of future events
 	 *
 	 * @return Response
 	 */
	public function feed()
	{
		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		$this->rpp = 10000;

		$events = Event::future()->simplePaginate($this->rpp);
		$events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		return view('events.feed', compact('events'));
	}


	/**
 	 * Send a reminder to all users who are attending this event
 	 *
 	 * @return Response
 	 */
	public function remind($id)
	{
		if (!$event = Event::find($id))
		{
			flash()->error('Error',  'No such event');
			return back();
		};

		// get all the users attending
		foreach ($event->eventResponses as $response)
		{
			$user = User::findOrFail($response->user_id);

			Mail::send('emails.reminder', ['user' => $user, 'event' => $event], function ($m) use ($user, $event) {
				$m->from('admin@events.cutupsmethod.com','Event Repo');

				$m->to($user->email, $user->name)->subject('Event Repo: '.$event->start_at->format('D F jS').' '.$event->name.' REMINDER');
			});
		}

		flash()->success('Success',  'You sent an email reminder to '.count($event->eventResponses).' user about '.$event->name);

		return back();
	}


	/**
 	 * Send a reminder to all users about all events they are attending
 	 *
 	 * @return Response
 	 */
	public function daily()
	{
		// get all the users
		$users = User::orderBy('name','ASC')->get();

		// cycle through all the users
		foreach ($users as $user)
		{
			$events = $user->getAttendingFuture()->take(100);

			Mail::send('emails.daily-events', ['user' => $user, 'events' => $events], function ($m) use ($user, $events) {
				$m->from('admin@events.cutupsmethod.com','Event Repo');

				$m->to($user->email, $user->name)->subject('Event Repo: Daily Events Reminder');
			});
		
		};

		flash()->success('Success',  'You sent an email reminder to '.count($users).' users about events they are attending');

		return back();
	}


	/**
	 * Display a listing of events by tag
	 *
	 * @return Response
	 */
	public function calendarTags($tag)
	{
 		$tag = urldecode($tag);

 		$eventList = array();

		$events = Event::getByTag(ucfirst($tag))
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->get();

		$events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});


		foreach ($events as $event)
		{
			$eventList[] = \Calendar::event(
			    $event->name, //event title
			    false, //full day event?
			    $event->start_at->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
			    ($event->end_time ? $event->end_time->format('Y-m-d H:i') : NULL), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
			    $event->id, //optional event ID
			    [
			        'url' => 'events/'.$event->id,
			        //'color' => '#fc0'
			    ]
			);
		};

		// get all the upcoming series events
		$series = Series::getByTag(ucfirst($tag))->active()->get();

		$series = $series->filter(function($e)
		{
			// all public events
			// all events that you created
			// all events that you are invited to
			return ((($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id)) AND $e->occurrenceType->name != 'No Schedule');
		});

		foreach ($series as $s)
		{
			if ($s->nextEvent() == NULL AND $s->nextOccurrenceDate() != NULL)
			{
				// add the next instance of each series to the calendar
				$eventList[] = \Calendar::event(
				    $s->name, //event title
				    false, //full day event?
				    $s->nextOccurrenceDate()->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
				    ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : NULL), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
				    $s->id, //optional event ID
				    [
				        'url' => 'series/'.$s->id,
				        'color' => '#99bcdb'
				    ]
				);
			};
		};


		$calendar = \Calendar::addEvents($eventList) //add an array with addEvents
		    ->setOptions([ //set fullcalendar options
		        'firstDay' => 0,
		        'height' => 840,
		    ])->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
		        //'viewRender' => 'function() {alert("Callbacks!");}'
		    ]); 
		return view('events.calendar', compact('calendar', 'tag'));

	}


	/**
	 * Display a calendar view of events
	 *
	 * @return view
	 **/
	public function calendar()
	{
		$eventList = array();
		
		// get all public events
		$events = Event::all();

		$events = $events->filter(function($e)
		{
			// all public events
			// all events that you created
			// all events that you are invited to
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		foreach ($events as $event)
		{
			$eventList[] = \Calendar::event(
			    $event->name, //event title
			    false, //full day event?
			    $event->start_at->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
			    ($event->end_time ? $event->end_time->format('Y-m-d H:i') : NULL), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
			    $event->id, //optional event ID
			    [
			        'url' => 'events/'.$event->id,
			        //'color' => '#fc0'
			    ]
			);
		};

		// get all the upcoming series events
		$series = Series::active()->get();

		$series = $series->filter(function($e)
		{
			// all public events
			// all events that you created
			// all events that you are invited to
			return ((($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id)) AND $e->occurrenceType->name != 'No Schedule');
		});

		foreach ($series as $s)
		{
			if ($s->nextEvent() == NULL AND $s->nextOccurrenceDate() != NULL)
			{
				// add the next instance of each series to the calendar
				$eventList[] = \Calendar::event(
				    $s->name, //event title
				    false, //full day event?
				    $s->nextOccurrenceDate()->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
				    ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : NULL), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
				    $s->id, //optional event ID
				    [
				        'url' => 'series/'.$s->id,
				        'color' => '#99bcdb'
				    ]
				);
			};
		};

		$calendar = \Calendar::addEvents($eventList) //add an array with addEvents
		    ->setOptions([ //set fullcalendar options
		        'firstDay' => 0,
		        'height' => 840,
		    ])->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
		        //'viewRender' => 'function() {alert("Callbacks!");}'
		    ]); 
		return view('events.calendar', compact('calendar'));
	}

	/**
	 * Show a form to create a new Article.
	 *
	 * @return view
	 **/

	public function create()
	{
		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->pluck('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->pluck('name', 'id')->all(); 
		$seriesList = [''=>''] + Series::orderBy('name','ASC')->pluck('name', 'id')->all(); 
		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();

		return view('events.create', compact('venues','eventTypes','visibilities','tags','entities','promoters','seriesList'));
	}

	public function show(Event $event)
	{
		return view('events.show', compact('event'));
	}


	public function store(EventRequest $request, Event $event)
	{
		$msg = '';

		// get the request
		$input = $request->all();

		// validate - hmm, isn't this doing it elsewhere?

		$tagArray = $request->input('tag_list',[]);
		$syncArray = array();

		// check the elements in the tag list, and if any don't match, add the tag
		foreach ($tagArray as $key => $tag)
		{

			if (!DB::table('tags')->where('id', $tag)->get())
			{
				$newTag = new Tag;
				$newTag->name = ucwords(strtolower($tag));
				$newTag->tag_type_id = 1;
				$newTag->save();

				$syncArray[] = $newTag->id;

				$msg .= ' Added tag '.$tag.'.';
			} else {
				$syncArray[$key] = $tag;
			};
		}

		$event = $event->create($input);

		$event->tags()->attach($syncArray);
		$event->entities()->attach($request->input('entity_list'));

		// here, make a call to notify all users who are following any of the sync'd tags
		$this->notifyFollowing($event);

		// add to activity log
		Activity::log($event, $this->user, 1);

		flash()->success('Success', 'Your event has been created');

		return redirect()->route('events.index');
	}

	protected function notifyFollowing($event)
	{
		// notify users following any of the tags
		$tags = $event->tags()->get();
		$users = array();

		// improve this so it will only sent one email to each user per event, and include a list of all tags they were following that led to the notification
		foreach ($tags as $tag)
		{
			foreach ($tag->followers() as $user)
			{
				// if the user hasn't already been notified, then email them
				if (!array_key_exists($user->id, $users))
				{
					Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $tag], function ($m) use ($user, $event, $tag) {
						$m->from('admin@events.cutupsmethod.com','Event Repo');

						$m->to($user->email, $user->name)->subject('Event Repo: '.$tag->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
					});
					$users[$user->id] = $tag->name;
				};
			};
		};

		// notify users following any of the entities
		$entities = $event->entities()->get();

		// improve this so it will only sent one email to each user per event, and include a list of entities they were following that led to the notification
		foreach ($entities as $entity)
		{
			foreach ($entity->followers() as $user)
			{

				// if the user hasn't already been notified, then email them
				if (!array_key_exists($user->id, $users))
				{
					Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $entity], function ($m) use ($user, $event, $entity) {
						$m->from('admin@events.cutupsmethod.com','Event Repo');

						$m->to($user->email, $user->name)->subject('Event Repo: '.$entity->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
					});
					$users[$user->id] = $entity->name;
				};
			};
		};

		return back();
	}

	protected function unauthorized(EventRequest $request)
	{
		if($request->ajax())
		{
			return response(['message' => 'No way.'], 403);
		}

		\Session::flash('flash_message', 'Not authorized');

		return redirect('/');
	}

	public function edit(Event $event)
	{
		$this->middleware('auth');

		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->pluck('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->pluck('name', 'id')->all();
		$visibilities = [''=>''] + Visibility::pluck('name', 'id')->all();
		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();

		$seriesList = [''=>''] + Series::pluck('name', 'id')->all();

		return view('events.edit', compact('event', 'venues', 'promoters','eventTypes', 'visibilities','tags','entities','seriesList'));
	}

	public function update(Event $event, EventRequest $request)
	{
		$msg = '';

		$event->fill($request->input())->save();

		if (!$event->ownedBy($this->user))
		{
			$this->unauthorized($request); 
		};

		$tagArray = $request->input('tag_list',[]);
		$syncArray = array();

		// check the elements in the tag list, and if any don't match, add the tag
		foreach ($tagArray as $key => $tag)
		{

			if (!DB::table('tags')->where('id', $tag)->get())
			{
				$newTag = new Tag;
				$newTag->name = ucwords(strtolower($tag));
				$newTag->tag_type_id = 1;
				$newTag->save();

				$syncArray[] = $newTag->id;

				$msg .= ' Added tag '.$tag.'.';
			} else {
				$syncArray[$key] = $tag;
			};
		}

		$event->tags()->sync($syncArray);
		$event->entities()->sync($request->input('entity_list',[]));

		// add to activity log
		Activity::log($event, $this->user, 2);

		flash('Success', 'Your event has been updated');

		return redirect('events');
	}

	public function destroy(Event $event)
	{

		// add to activity log
		Activity::log($event, $this->user, 3);

		$event->delete();

		flash()->success('Success', 'Your event has been deleted!');


		return redirect('events');
	}


	/**
	 * Mark user as attending the event.
	 *
	 * @return Response
	 */
	public function attending($id, Request $request)
	{

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$event = Event::find($id))
		{
			flash()->error('Error',  'No such event');
			return back();
		};

		// add the attending response
		$response = new EventResponse;
		$response->event_id = $id;
		$response->user_id = $this->user->id;
		$response->response_type_id = 1; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
		$response->save();

		// add to activity log
		Activity::log($event, $this->user, 6);

     	Log::info('User '.$id.' is attending '.$event->name);

		flash()->success('Success',  'You are now attending the event - '.$event->name);

		return back();

	}

	/**
	 * Mark user as unattending the event.
	 *
	 * @return Response
	 */
	public function unattending($id, Request $request)
	{

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$event = Event::find($id))
		{
			flash()->error('Error',  'No such event');
			return back();
		};

		// delete the attending response
		$response = EventResponse::where('event_id','=', $id)->where('user_id','=',$this->user->id)->where('response_type_id','=',1)->first();
		//dd($response);
		$response->delete();

		// add to activity log
		Activity::log($event, $this->user, 7);

		flash()->success('Success',  'You are no longer attending the event - '.$event->name);

		return back();

	}

	/**
	 * Record a user's review of the event
	 *
	 * @return Response
	 */
	public function review($id, Request $request)
	{

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$event = Event::find($id))
		{
			flash()->error('Error',  'No such event');
			return back();
		};

		// add the event review 
		$review = new EventReview;
		$review->event_id = $id;
		$review->user_id = $this->user->id;
		$review->review_type_id = 1; // 1 = Informational, 2 = Positive, 3 = Neutral, 4 = Negative
        $review->attended = $request->input('attended', 0);
        $review->confirmed = $request->input('confirmed', 0);
        $review->expecation = $request->input('expectation', NULL);
        $review->rating = $request->input('rating', NULL);
        $review->review = $request->input('review', NULL);
        $review->created_by = $this->user->id;
		$review->save();

		flash()->success('Success',  'You reviewed the event - '.$event->name);

		return back();

	}

	/**
	 * Display a listing of events by tag
	 *
	 * @return Response
	 */
	public function indexTags($tag)
	{
 		$tag = urldecode($tag);

		$future_events = Event::getByTag(ucfirst($tag))->future()
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->simplePaginate($this->rpp);

		$future_events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});


		$past_events = Event::getByTag(ucfirst($tag))->past()
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->simplePaginate($this->rpp);


		$past_events->filter(function($e)
		{
			return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		return view('events.index', compact('future_events', 'past_events', 'tag'));
	}

	/**
	 * Display a listing of events related to entity
	 *
	 * @return Response
	 */
	public function indexRelatedTo($slug)
	{
 		$slug = urldecode($slug);

		$future_events = Event::getByEntity(strtolower($slug))
					->future()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		$past_events = Event::getByEntity(strtolower($slug))
					->past()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		return view('events.index', compact('future_events', 'past_events', 'slug'));
	}

	/**
	 * Display a listing of events that start on the specified day
	 *
	 * @return Response
	 */
	public function indexStarting($date)
	{
		$cdate = Carbon::parse($date);
		$cdate_yesterday = Carbon::parse($date)->subDay(1);
		$cdate_tomorrow = Carbon::parse($date)->addDay(1);

		$future_events = Event::where('start_at','>', $cdate_yesterday->toDateString())
					->where('start_at','<',$cdate_tomorrow->toDateString())
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		return view('events.index', compact('future_events', 'cdate'));
	} 

	/**
	 * Display a listing of events by venue
	 *
	 * @return Response
	 */
	public function indexVenues($slug)
	{
 
		$future_events = Event::getByVenue(strtolower($slug))
					->future()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		$past_events = Event::getByVenue(strtolower($slug))
					->past()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();


		return view('events.index', compact('future_events', 'past_events', 'slug'));
	}



	/**
	 * Display a listing of events by type
	 *
	 * @return Response
	 */
	public function indexTypes($slug)
	{
 		$slug = urldecode($slug);

		$future_events = Event::getByType($slug)
					->future()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->paginate();

		$past_events = Event::getByType($slug)
					->past()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->paginate();


		return view('events.index', compact('future_events', 'past_events', 'slug'));
	}

	/**
	 * Display a listing of events by series
	 *
	 * @return Response
	 */
	public function indexSeries($slug)
	{
 		$slug = urldecode($slug);

		$future_events = Event::getBySeries(strtolower($slug))
					->future()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		$past_events = Event::getBySeries(strtolower($slug))
					->past()
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();


		return view('events.index', compact('future_events', 'past_events', 'slug'));
	}

	/**
	 * Display a listing of events in a week view
	 *
	 * @return Response
	 */
	public function indexWeek()
	{
		$this->rpp = 5;

		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		$events = Event::future()->get();
		$events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});


		return view('events.indexWeek', compact('events'));
	}


	/**
	 * Add a photo to an event
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function addPhoto($id, Request $request)
	{

		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

		$photo = $this->makePhoto($request->file('file'));
		$photo->save();

		// attach to event
		$event = Event::find($id);
		$event->addPhoto($photo);
	}
	
	/**
	 * Delete a photo
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function deletePhoto($id, Request $request)
	{

		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

		// detach from event
		$event = Event::find($id);
		$event->removePhoto($photo);

		$photo = $this->deletePhoto($request->file('file'));
		$photo->save();


	}

	protected function makePhoto(UploadedFile $file)
	{
		return Photo::named($file->getClientOriginalName())
			->move($file);
	}

	/**
	 * Mark user as following the event
	 *
	 * @return Response
	 */
	public function follow($id, Request $request)
	{
		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$event = Event::find($id))
		{
			flash()->error('Error',  'No such event');
			return back();
		};

		// add the following response
		$follow = new Follow;
		$follow->object_id = $id;
		$follow->user_id = $this->user->id;
		$follow->object_type = 'event'; // 
		$follow->save();

     	Log::info('User '.$id.' is following '.$event->name);

		flash()->success('Success',  'You are now following the event - '.$event->name);

		return back();

	}

	/**
	 * Mark user as unfollowing the entity.
	 *
	 * @return Response
	 */
	public function unfollow($id, Request $request)
	{

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$event = Event::find($id))
		{
			flash()->error('Error',  'No such event');
			return back();
		};

		// delete the follow
		$response = Follow::where('object_id','=', $id)->where('user_id','=',$this->user->id)->where('object_type','=','event')->first();
		$response->delete();

		flash()->success('Success',  'You are no longer following the event.');

		return back();
	}


	public function rss(RssFeed $feed)
	{
	    $rss = $feed->getRSS();

	    return response($rss)
	      ->header('Content-type', 'application/rss+xml');
  	}



}
