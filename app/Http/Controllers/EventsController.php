<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Entity;
use App\Event;
use App\EventResponse;
use App\EventReview;
use App\Events\EventCreated;
use App\Events\EventUpdated;
use App\EventType;
use App\Http\Requests\EventRequest;
use App\Notifications\EventPublished;
use App\OccurrenceDay;
use App\OccurrenceType;
use App\OccurrenceWeek;
use App\Photo;
use App\Series;
use App\Services\RssFeed;
use App\Tag;
use App\Thread;
use App\User;
use App\Visibility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Scottybo\LaravelFacebookSdk\LaravelFacebookSdk;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Facebook\Exceptions\FacebookSDKException;

class EventsController extends Controller
{
    protected string $prefix;
    protected int $rpp;
    protected int $defaultRpp;
    protected string $defaultSortBy;
    protected string $defaultSortOrder;
    protected int $gridRpp;
    protected int $page;
    protected array $sort;
    protected string $sortBy;
    protected string $sortOrder;
    protected $defaultCriteria;
    protected array $filters;
    protected bool $hasFilter;
    protected Event $event;
    protected $criteria;
    protected LaravelFacebookSdk $fb;

    public function __construct(Event $event, LaravelFacebookSdk $fb)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'indexAttending', 'calendarAttending']]);
        $this->event = $event;

        // prefix for session storage
        $this->prefix = 'app.events.';

        // default list variables
        $this->defaultRpp = 10;
        $this->defaultSortBy = 'name';
        $this->defaultSortOrder = 'asc';

        $this->sortBy = 'name';
        $this->sortOrder = 'asc';
        $this->rpp = 10;
        $this->gridRpp = 24;
        $this->page = 1;
        $this->sort = ['name', 'desc'];

        $this->fb = $fb;

        $this->defaultCriteria = null;
        $this->hasFilter = 0;
        parent::__construct();
    }

    /**
     * Gets the reporting options from the request and saves to session.
     */
    public function getReportingOptions(Request $request)
    {
        foreach (['page', 'rpp', 'sort', 'criteria'] as $option) {
            if (!$request->has($option)) {
                continue;
            }
            if ('sort' === $option) {
                $value = [
                    $request->input($option),
                    $request->input('sort_order', 'asc'),
                ];
            } else {
                $value = $request->input($option);
            }
            $this->{sprintf('set%s', ucwords($option))}($value);
        }
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getAttribute(string $attribute, Request $request, $default = null)
    {
        return $request->session()
            ->get($this->prefix.$attribute, $default);
    }

    /**
     * Set user session attribute.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    public function setAttribute(string $attribute, $value, Request $request)
    {
        $request->session()->put($this->prefix.$attribute, $value);

        return true;
    }

    public function getDefaultSort(): array
    {
        return ['id', 'desc'];
    }

    /**
     * Display a listing of the resource.
     *
     * @return View|string
     *
     * @throws \Throwable
     */
    public function index(Request $request): string
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($filters);

        // base criteria
        $query_past = $this->buildCriteria($request); //,'start_at', 'desc' );
        $query_future = $this->buildCriteria($request); //, 'start_at', 'asc');

        $query_past->past();
        $query_future->future();

        // build future events query
        $query_future
            // public or where created by
            ->where(function ($query) {
                $query->whereIn('visibility_id', [1, 2])
                    ->where('created_by', '=', $this->user ? $this->user->id : null);
                // if logged in, can see guarded
                if ($this->user) {
                    $query->orWhere('visibility_id', '=', 4);
                }
                $query->orWhere('visibility_id', '=', 3);

                return $query;
            });

        // get future events
        $future_events = $query_future
            ->with('visibility', 'venue')
            ->paginate($this->rpp);

        // build past events query
        $query_past
            // public or where created by
            ->where(function ($query) {
                $query->whereIn('visibility_id', [1, 2])
                    ->where('created_by', '=', $this->user ? $this->user->id : null);
                // if logged in, can see guarded
                if ($this->user) {
                    $query->orWhere('visibility_id', '=', 4);
                }
                $query->orWhere('visibility_id', '=', 3);

                return $query;
            });

        // get past events
        $past_events = $query_past
            ->with('visibility', 'venue')
            ->paginate($this->rpp);

        return view('events.index')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'hasFilter' => $this->hasFilter,
                'filters' => $filters,
            ])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->render();
    }

    /**
     * Update the page list parameters from the request.
     */
    protected function updatePaging(Request $request)
    {
        if (!empty($request->input('sort_by'))) {
            $this->sortBy = $request->input('sort_by');
        }

        if (!empty($request->input('sort_order'))) {
            $this->sortOrder = $request->input('sort_order');
        }

        if (!empty($request->input('rpp'))) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Update the page list parameters from the request.
     *
     * @param $filters
     */
    protected function getPaging($filters)
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        $this->rpp = $filters['rpp'] ?? $this->rpp;
    }


    protected function getGridPaging(array $filters): void
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        $this->rpp = $filters['rpp'] ?? $this->gridRpp;
    }


    public function getFilters(Request $request): array
    {
        return $this->getAttribute('filters', $request, $this->getDefaultFilters());
    }

    public function setFilters(Request $request, array $input)
    {
        // only set if the key starts with filter
        return $this->setAttribute('filters', $input, $request);
    }

    public function getDefaultFilters(): array
    {
        return [];
    }

    public function buildCriteria(Request $request): Builder
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = Event::query();

        // add the criteria from the session
        // check request for passed filter values
        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%'.$name.'%');
            $filters['filter_name'] = $name;
        }

        if (!empty($filters['filter_tag'])) {
            $tag = $filters['filter_tag'];
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });

            // add to filters array
            $filters['filter_tag'] = $tag;
        }

        if (!empty($filters['filter_venue'])) {
            $venue = $filters['filter_venue'];
            // add has clause
            $query->whereHas('venue', function ($q) use ($venue) {
                $q->where('name', '=', $venue);
            });

            // add to filters array
            $filters['filter_venue'] = $venue;
        }

        if (!empty($filters['filter_related'])) {
            $related = $filters['filter_related'];
            $query->whereHas('entities', function ($q) use ($related) {
                $q->where('name', '=', ucfirst($related));
            });

            // add to filters array
            $filters['filter_related'] = $related;
        }

        // change this - should be separate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

    /**
     * Display a grid listing of the resource.
     *
     * @return View | string
     *
     * @throws \Throwable
     */
    public function grid(Request $request): string
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // get sort, sort order, rpp from session, update from request
        $this->getGridPaging($this->filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        // base criteria
        $query = $this->buildCriteria($request); //,'start_at', 'desc' );

        // get future events
        $events = $query->orderBy('created_at', 'desc')->paginate($this->rpp);
        $events->filter(function ($e) {
            return (isset($e->visibility) && 'Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        return view('events.grid')
            ->with([
                'rpp' => $this->gridRpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'hasFilter' => $this->hasFilter,
                'filters' => $this->filters,
            ])
            ->with(compact('events'))
            ->render();
    }

    /**
     * Update the filters parameters from the request.
     *
     * @param $request
     */
    protected function updateFilters($request)
    {
        $filters = [];

        if (!empty($request->input('filter_name'))) {
            $filters['filter_name'] = $request->input('filter_name');
        }

        if (!empty($request->input('filter_venue'))) {
            $filters['filter_venue'] = $request->input('filter_venue');
        }

        if (!empty($request->input('filter_tag'))) {
            $filters['filter_tag'] = $request->input('filter_tag');
        }

        if (!empty($request->input('filter_related'))) {
            $filters['filter_related'] = $request->input('filter_related');
        }

        // save filters to session
        $this->setFilters($request, $filters);
    }

    /**
     * Display a listing of events from this point in time both future and past.
     *
     * @return View | string
     *
     * @throws \Throwable
     */
    public function indexTimeline(Request $request)
    {
        $hasFilter = 1;

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        // base criteria
        $query_past = $this->buildCriteria($request);
        $query_future = $this->buildCriteria($request);

        $query_past->past();
        $query_future->future();

        // get future events
        $future_events = $query_future->where('start_at', '>', Carbon::today()->startOfDay())
            ->where(function ($query) {
                return $query->where('visibility_id', '=', 3)
                    ->orWhere('created_by', '=', $this->user ? $this->user->id : null);
            })
            ->with('visibility')->paginate($this->rpp);

        // get past events
        $past_events = $query_past->where('start_at', '<', Carbon::today()->startOfDay())->where(function ($query) {
            return $query->where('visibility_id', '=', 3)
                ->orWhere('created_by', '=', $this->user ? $this->user->id : null);
        })
            ->with('visibility')->paginate($this->rpp);

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter, 'filters' => $filters,
            ])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->render();
    }

    /**
     * Checks if there is a valid filter.
     *
     * @param $filters
     */
    public function hasFilter($filters): bool
    {
        if (!is_array($filters)) {
            return false;
        }

        unset($filters['rpp'], $filters['sortOrder'], $filters['sortBy'], $filters['page']);

        return count(array_filter($filters, function ($x) { return !empty($x); }));
    }

    /**
     * Filter the list of events.
     *
     * @return View | string
     *
     * @throws \Throwable
     */
    public function filter(Request $request)
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($this->filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        // base criteria
        $query_future = $this->event->future()->orderBy($this->sortBy, $this->sortOrder);
        $query_past = $this->event->past()->orderBy($this->sortBy, $this->sortOrder);

        // add the criteria from the session - move this?
        if (!empty($this->filters['filter_name'])) {
            $query_future->where('name', 'like', '%'.$this->filters['filter_name'].'%');
            $query_past->where('name', 'like', '%'.$this->filters['filter_name'].'%');
        }

        if (!empty($this->filters['filter_venue'])) {
            $venue = $this->filters['filter_venue'];
            // add has clause
            $query_future->whereHas('venue', function ($q) use ($venue) {
                $q->where('name', '=', $venue);
            });
            $query_past->whereHas('venue', function ($q) use ($venue) {
                $q->where('name', '=', $venue);
            });
        }

        if (!empty($this->filters['filter_tag'])) {
            $tag = $this->filters['filter_tag'];
            $query_future->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });
            $query_past->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });
        }

        if (!empty($this->filters['filter_related'])) {
            $related = $this->filters['filter_related'];
            $query_future->whereHas('entities', function ($q) use ($related) {
                $q->where('name', '=', ucfirst($related));
            });
            $query_past->whereHas('entities', function ($q) use ($related) {
                $q->where('name', '=', ucfirst($related));
            });
        }

        // get future events
        $future_events = $query_future->paginate($this->rpp);

        $future_events->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get past events
        $past_events = $query_past->paginate($this->rpp);

        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.index')
            ->with([
                'rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'hasFilter' => $this->hasFilter,
                'filters' => $this->filters,
            ])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->render();
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function indexAll(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $future_events = Event::future()->paginate(100000);
        $future_events->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        $past_events = Event::past()->paginate(100000);
        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response | View
     */
    public function indexFuture(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $this->rpp = 10;

        $future_events = Event::future()->paginate($this->rpp);
        $future_events->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'));
    }

    /**
     * Display a listing of today's events.
     *
     * @return Response | View
     */
    public function indexToday(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $this->rpp = 10;

        $events = Event::today()->paginate($this->rpp);
        $events->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('events'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response | View
     */
    public function indexPast(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $this->rpp = 10;

        $past_events = Event::past()->paginate($this->rpp);
        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('past_events'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response | View
     */
    public function indexAttending(Request $request)
    {
        $this->middleware('auth');

        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($filters);

        $events = $this->user->getAttending()->paginate($this->rpp);

        $events->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.index')
            ->with(['tag' => 'Attending', 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('events'));
    }

    /**
     * Display a simple text feed of future events.
     *
     * @return Response | View
     */
    public function feed()
    {
        // set number of results per page
        $this->rpp = 10000;

        $events = Event::future()->simplePaginate($this->rpp);
        $events->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.feed', compact('events'));
    }

    /**
     * Display a simple text feed of future events by tag.
     *
     * @return Response | View
     */
    public function feedTags($tag)
    {
        // set number of results per page
        $this->rpp = 10000;

        $events = Event::getByTag(ucfirst($tag))->future()->simplePaginate($this->rpp);
        $events->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.feed', compact('events'));
    }

    /**
     * Reset the filtering of entities.
     *
     * @return RedirectResponse | View
     */
    public function reset(Request $request)
    {
        // doesn't have filter, but temp
        $hasFilter = 0;

        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        // base criteria
        $query_future = $this->event->future();
        $query_past = $this->event->past();

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get future events
        $future_events = $query_future->paginate($this->rpp);
        $future_events->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get past events
        $past_events = $query_past->paginate($this->rpp);
        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        if ($redirect = $request->input('redirect')) {
            return redirect()->route($redirect);
        }

        return redirect()->route('events.index');
    }

    /**
     * Send a reminder to all users who are attending this event.
     *
     * @param $id
     *
     * @return Response | RedirectResponse
     */
    public function remind($id, Mail $mail)
    {
        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        // get all the users attending
        foreach ($event->eventResponses as $response) {
            $user = User::findOrFail($response->user_id);

            $mail->send('emails.reminder', ['user' => $user, 'event' => $event], static function ($m) use ($user, $event) {
                $m->from('admin@events.cutupsmethod.com', 'Event Repo');

                $m->to($user->email, $user->name)->subject('Event Repo: '.$event->start_at->format('D F jS').' '.$event->name.' REMINDER');
            });
        }

        flash()->success('Success', 'You sent an email reminder to '.count($event->eventResponses).' user about '.$event->name);

        return back();
    }

    /**
     * Get the events for one passed day.
     *
     * @param $day
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function day(Request $request, $day)
    {
        if (!$day) {
            flash()->error('Error', 'No such day');

            return back();
        }
        $day = \Carbon\Carbon::parse($day);

        return view('events.day')
            ->with(['day' => $day, 'position' => 0, 'offset' => 0])
            ->render();
    }

    /**
     * Send a reminder to all users about all events they are attending.
     *
     * @return Response | RedirectResponse
     */
    public function daily()
    {
        // get all the users
        $users = User::orderBy('name', 'ASC')->get();

        // cycle through all the users
        foreach ($users as $user) {
            $events = $user->getAttendingFuture()->take(100);

            Mail::send('emails.daily-events', ['user' => $user, 'events' => $events], function ($m) use ($user, $events) {
                $m->from('admin@events.cutupsmethod.com', 'Event Repo');

                $m->to($user->email, $user->name)->subject('Event Repo: Daily Events Reminder');
            });
        }

        flash()->success('Success', 'You sent an email reminder to '.count($users).' users about events they are attending');

        return back();
    }

    /**
     * Display a listing of events related to entity.
     *
     * @param $slug
     *
     * @return Response
     */
    public function calendarRelatedTo(Request $request, $slug)
    {
        $slug = urldecode($slug);

        $eventList = [];

        // get all events related to the entity
        $events = Event::getByEntity(strtolower($slug))
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        foreach ($events as $event) {
            $eventList[] = \Calendar::event(
                $event->name, //event title
                false, //full day event?
                $event->start_at->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                ($event->end_time ? $event->end_time->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                $event->id, //optional event ID
                [
                    'url' => 'events/'.$event->id,
                    //'color' => '#fc0'
                ]
            );
        }

        // get all the upcoming series events
        $series = Series::getByEntity(ucfirst($slug))->active()->get();

        $series = $series->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        foreach ($series as $s) {
            if (null == $s->nextEvent() and null != $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = \Calendar::event(
                    $s->name, //event title
                    false, //full day event?
                    $s->nextOccurrenceDate()->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                    ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                    $s->id, //optional event ID
                    [
                        'url' => 'series/'.$s->id,
                        'color' => '#99bcdb',
                    ]
                );
            }
        }

        $calendar = \Calendar::addEvents($eventList)//add an array with addEvents
        ->setOptions([ //set fullcalendar options
            'firstDay' => 0,
            'height' => 840,
        ])->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
            //'viewRender' => 'function() {alert("Callbacks!");}'
        ]);

        return view('events.calendar', compact('calendar', 'slug'));
    }

    /**
     * Display a listing of events by tag.
     *
     * @param $tag
     *
     * @return Response
     */
    public function calendarTags($tag)
    {
        $tag = urldecode($tag);

        $eventList = [];

        $events = Event::getByTag(ucfirst($tag))
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        foreach ($events as $event) {
            $eventList[] = \Calendar::event(
                $event->name, //event title
                false, //full day event?
                $event->start_at->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                ($event->end_time ? $event->end_time->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                $event->id, //optional event ID
                [
                    'url' => 'events/'.$event->id,
                    //'color' => '#fc0'
                ]
            );
        }

        // get all the upcoming series events
        $series = Series::getByTag(ucfirst($tag))->active()->get();

        $series = $series->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        foreach ($series as $s) {
            if (null == $s->nextEvent() and null != $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = \Calendar::event(
                    $s->name, //event title
                    false, //full day event?
                    $s->nextOccurrenceDate()->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                    ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                    $s->id, //optional event ID
                    [
                        'url' => 'series/'.$s->id,
                        'color' => '#99bcdb',
                    ]
                );
            }
        }

        $calendar = \Calendar::addEvents($eventList)//add an array with addEvents
        ->setOptions([ //set fullcalendar options
            'firstDay' => 0,
            'height' => 840,
        ])->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
            //'viewRender' => 'function() {alert("Callbacks!");}'
        ]);

        return view('events.calendar', compact('calendar', 'tag'));
    }

    /**
     * Display a calendar view of events.
     *
     * @return view
     **/
    public function calendar()
    {
        // get all public events
        $events = Event::all();

        $events = $events->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::active()->get();

        $series = $series->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by === $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        return $this->renderCalendar($events, $series);
    }

    /**
     * Displays the calendar based on passed events and tag.
     *
     * @param $events
     * @param array | null $series
     * @param null         $tag
     *
     * @return view
     */
    public function renderCalendar($events, $series = null, $tag = null)
    {
        $eventList = [];

        foreach ($events as $event) {
            $eventList[] = \Calendar::event(
                $event->name, //event title
                false, //full day event?
                $event->start_at->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                ($event->end_time ? $event->end_time->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                $event->id, //optional event ID
                [
                    'url' => '/events/'.$event->id,
                    //'color' => '#fc0'
                ]
            );
        }

        foreach ($series as $s) {
            if (null === $s->nextEvent() && null !== $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = \Calendar::event(
                    $s->name, //event title
                    false, //full day event?
                    $s->nextOccurrenceDate()->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                    ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                    $s->id, //optional event ID
                    [
                        'url' => '/series/'.$s->id,
                        'color' => '#99bcdb',
                    ]
                );
            }
        }

        $calendar = \Calendar::addEvents($eventList)//add an array with addEvents
        ->setOptions([ //set fullcalendar options
            'firstDay' => 0,
            'height' => 840,
        ])->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
            //'viewRender' => 'function() {alert("Callbacks!");}'
        ]);

        return view('events.calendar', compact('calendar', 'tag'));
    }

    /**
     * Display a calendar view of events you are attending.
     *
     * @return view
     **/
    public function calendarAttending()
    {
        $this->middleware('auth');

        $events = $this->user->getAttending()
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events = $events->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = $this->user->getSeriesFollowing();

        $series = $series->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        $tag = 'Attending';

        return $this->renderCalendar($events, $series, $tag);
    }

    /**
     * Display a calendar view of free events.
     *
     * @return view
     **/
    public function calendarFree()
    {
        $events = Event::where('door_price', 0)
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events = $events->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::where('door_price', 0)->active()->get();

        $series = $series->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        $tag = 'No Cover';

        return $this->renderCalendar($events, $series, $tag);
    }

    /**
     * Display a calendar view of all ages.
     *
     * @param $age
     *
     * @return view
     */
    public function calendarMinAge($age)
    {
        $age = urldecode($age);

        $events = Event::where('min_age', '<=', $age)
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events = $events->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::where('min_age', '<=', $age)->active()->get();

        $series = $series->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        $tag = 'Min Age '.$age;

        return $this->renderCalendar($events, $series, $tag);
    }

    /**
     * Display a listing of events by event type.
     *
     * @param $type
     *
     * @return Response
     */
    public function calendarEventTypes($type)
    {
        $tag = urldecode($type);

        $eventList = [];

        $events = Event::getByType(ucfirst($tag))
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        foreach ($events as $event) {
            $eventList[] = \Calendar::event(
                $event->name, //event title
                false, //full day event?
                $event->start_at->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                ($event->end_time ? $event->end_time->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                $event->id, //optional event ID
                [
                    'url' => 'events/'.$event->id,
                    //'color' => '#fc0'
                ]
            );
        }

        // get all the upcoming series events
        $series = Series::getByType(ucfirst($tag))->active()->get();

        $series = $series->filter(function ($e) {
            // all public events
            // all events that you created
            // all events that you are invited to
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        foreach ($series as $s) {
            if (null == $s->nextEvent() and null != $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = \Calendar::event(
                    $s->name, //event title
                    false, //full day event?
                    $s->nextOccurrenceDate()->format('Y-m-d H:i'), //start time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
                    ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null), //end time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg),
                    $s->id, //optional event ID
                    [
                        'url' => 'series/'.$s->id,
                        'color' => '#99bcdb',
                    ]
                );
            }
        }

        $calendar = \Calendar::addEvents($eventList)//add an array with addEvents
        ->setOptions([ //set fullcalendar options
            'firstDay' => 0,
            'height' => 840,
        ])->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
            //'viewRender' => 'function() {alert("Callbacks!");}'
        ]);

        return view('events.calendar', compact('calendar', 'tag'));
    }

    /**
     * Show a form to create a new Article.
     *
     * @return view
     **/
    public function create()
    {
        // get a list of venues
        $venues = ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all();

        // get a list of promoters
        $promoters = ['' => ''] + Entity::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Promoter');
        })->orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $eventTypes = ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $seriesList = ['' => ''] + Series::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $userList = ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('events.create', compact('venues', 'eventTypes', 'visibilities', 'tags', 'entities', 'promoters', 'seriesList', 'userList'));
    }

    /**
     * Makes a call to the FB API if there is a link present and downloads the event cover photo.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importPhoto($id)
    {
        $event = Event::findOrFail($id);

        if (!$event->primary_link || '' == $event->primary_link) {
            flash()->error('Error', 'You must have a valid Facebook event linked to import the photo.  To add from your desktop, drop an image file to the right.');

            return back();
        }

        if ($this->addFbPhoto($event)) {
            flash()->success('Success', 'Successfully imported the event cover photo.');
        }

        return back();
    }

    /**
     * @param Event $event
     *
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    protected function addFbPhoto(Event $event)
    {
        // some fields may have been deprecated - only need cover here
        //$fields = 'attending_count,category,cover,interested_count,type,name,noreply_count,maybe_count,owner,place,roles';
        $fields = 'cover';

        $str = $event->primary_link;
        $spl = explode('/', $str);

        if (!isset($spl[4])) {
            flash()->error('Error', 'The link supplied does not have an importable photo.  Using a facebook event link is recommended.');

            return back();
        }

        $event_id = $spl[4];

        try {
            $token = $this->fb->getJavaScriptHelper()->getAccessToken();
            $response = $this->fb->get($event_id.'?fields='.$fields, $token);

            if ($cover = $response->getGraphNode()->getField('cover')) {
                $source = $cover->getField('source');
                $content = file_get_contents($source);

                $fileName = time().'_temp.jpg';
                file_put_contents('/var/www/dev-events/storage/app/public/photos/'.$fileName, $content);
                $file = new UploadedFile('/var/www/dev-events/storage/app/public/photos/'.$fileName, 'temp.jpg', null, null, UPLOAD_ERR_OK);

                // make the photo object from the file in the request
                if ($photo = $this->makePhoto($file)) {
                    // count existing photos, and if zero, make this primary
                    if (0 === count($event->photos)) {
                        $photo->is_primary = 1;
                    }

                    $photo->save();

                    // attach to event
                    $event->addPhoto($photo);
                }

            }
        } catch (FacebookSDKException $e) {
            flash()->error('Error', 'You could not import the image.  Error: '.$e->getMessage());

            return false;
        }

        return true;
    }

    protected function makePhoto(UploadedFile $file): Photo
    {
        $photo = Photo::named($file->getClientOriginalName());

        return $photo->makeThumbnail();
    }

    /**
     * Makes a call to the FB API if there is a link present and downloads the event cover photo.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importPhotos()
    {
        // get all the events with no photo, but a fb url
        $events = Event::has('photos', '<', 1)
            ->where('primary_link', '<>', '')
            ->where('primary_link', 'like', '%facebook%')
            ->get();

        $fields = 'attending_count,category,cover,interested_count,name,noreply_count,maybe_count';

        foreach ($events as $event) {
            $str = $event->primary_link;
            $spl = explode('/', $str);
            $event_id = $spl[4];

            $token = $this->fb->getJavaScriptHelper()->getAccessToken();
            $response = $this->fb->get($event_id.'?fields='.$fields, $token);

            // get the cover from FB
            if (($cover = $response->getGraphNode()->getField('cover')) && ($source = $cover->getField('source'))) {
                $content = file_get_contents($source);

                $fileName = time().'_temp.jpg';
                file_put_contents('/var/www/dev-events/storage/app/public/photos/'.$fileName, $content);
                $file = new UploadedFile('/var/www/dev-events/storage/app/public/photos/'.$fileName, 'temp.jpg', null, null, UPLOAD_ERR_OK);

                // make the photo object from the file in the request
                /** @var Photo $photo */
                if ($photo = $this->makePhoto($file)) {
                    // count existing photos, and if zero, make this primary
                    if (0 === count($event->photos)) {
                        $photo->is_primary = 1;
                    }

                    $photo->save();

                    // attach to event
                    /* @var Event $event */
                    $event->addPhoto($photo);
                }
            }
        }

        flash()->success('Success', 'Successfully imported the event cover photos.');

        return back();
    }

    /**
     * Makes a call to the FB API and posts the link to the specified event to the wall.
     *
     * @return \Illuminate\Contracts\View\Factory|View
     *
     * @throws \Illuminate\Container\EntryNotFoundException
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function postEvent(Event $event)
    {
        // Looks like this actually just writes hello on an event wall, but was used for testing
        if (empty((array) $event)) {
            abort(404);
        }

        $fields = 'attending_count,category,cover,interested_count,type,name,noreply_count,maybe_count,owner,place,roles';

        try {
            $token = $this->fb->getJavaScriptHelper()->getAccessToken();
            $response = $this->fb->get('1750886384941695?fields='.$fields, $token);

            $url = $response->cover->source;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            Log::info(sprintf('Error: %s', $e->getMessage()));
        }

        $userNode = $response->getGraphUser();
        printf('Hello, %s!', $userNode->getName());

        return view('events.show', compact('event'));
    }

    /**
     * Makes a call to the FB API if there is a link present and downloads the event cover photo.
     */
    public function getToken()
    {
        // Obtain an access token.
        try {
            $token = $this->fb->getAccessTokenFromRedirect();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }

        // Access token will be null if the user denied the request
        // or if someone just hit this URL outside of the OAuth flow.
        if (!$token) {
            // Get the redirect helper
            $helper = $this->fb->getRedirectLoginHelper();

            if (!$helper->getError()) {
                abort(403, 'Unauthorized action.');
            }

            // User denied the request
            dd(
                $helper->getError(),
                $helper->getErrorCode(),
                $helper->getErrorReason(),
                $helper->getErrorDescription()
            );
        }

        if (!$token->isLongLived()) {
            // OAuth 2.0 client handler
            $oauth_client = $this->fb->getOAuth2Client();

            // Extend the access token.
            try {
                $token = $oauth_client->getLongLivedAccessToken($token);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                dd($e->getMessage());
            }
        }

        $this->fb->setDefaultAccessToken($token);
    }

    /**
     * @return View
     */
    public function show(Event $event)
    {
        if (empty((array) $event)) {
            abort(404);
        }

        $thread = Thread::where('event_id', '=', $event->id)->get();

        return view('events.show', compact('event'))->with(['thread' => $thread ? $thread->first() : null]);
    }

    public function store(EventRequest $request, Event $event)
    {
        $msg = '';

        // get the request
        $input = $request->all();

        // transform the slug
        $input['slug'] = Str::slug($request->input('slug', '-'));

        // validation happening in EventRequest->rules
        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $event = $event->create($input);

        $event->tags()->attach($syncArray);
        $event->entities()->attach($request->input('entity_list'));

        // make a call to notify all users who are following any of the sync'd tags
        $this->notifyFollowing($event);

        // add to activity log
        Activity::log($event, $this->user, 1);

        EventCreated::dispatch($event);

        flash()->success('Success', 'Your event has been created');

        // check if a FB link was included
        if (false !== strpos($event->primary_link, 'facebook')) {
            // try to import the photo
            $this->addFbPhoto($event);
        }

        // add a twitter notification if the user is admin
        if ($this->user->hasGroup('super_admin')) {
            $event->notify(new EventPublished());
        }

        return redirect()->route('events.show', compact('event'));
    }

    /**
     * @param $event
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($event)
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $event->tags()->get();
        $users = [];

        // improve this so it will only sent one email to each user per event, and include a list of all tags they were following that led to the notification
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $tag, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
                    });
                    $users[$user->id] = $tag->name;
                }
            }
        }

        // notify users following any of the entities
        $entities = $event->entities()->get();

        // improve this so it will only sent one email to each user per event, and include a list of entities they were following that led to the notification
        foreach ($entities as $entity) {
            foreach ($entity->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $entity, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $entity, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$entity->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
                    });
                    $users[$user->id] = $entity->name;
                }
            }
        }

        return back();
    }

    public function edit(Event $event)
    {
        $this->middleware('auth');

        // moved necessary lists into AppServiceProvider

        return view('events.edit', compact('event'));
    }

    public function update(Event $event, EventRequest $request)
    {
        $msg = '';

        $event->fill($request->input())->save();

        if (!$event->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $event->tags()->sync($syncArray);
        $event->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($event, $this->user, 2);
        EventUpdated::dispatch($event);

        flash()->success('Success', 'Your event has been updated');

        //return redirect('events');
        return redirect()->route('events.show', compact('event'));
    }

    protected function unauthorized(EventRequest $request)
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
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
     * Tweet this event.
     *
     * @param $id
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function tweet($id)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        // add a twitter notification
        $event->notify(new EventPublished());

        Log::info('User '.$id.' tweeted '.$event->name);

        flash()->success('Success', 'You tweeted the event - '.$event->name);

        return back();
    }

    /**
     * Mark user as attending the event.
     *
     * @param $id
     *
     * @return Response | string | array
     *
     * @throws \Throwable
     */
    public function attend($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        // add the attending response
        $response = new EventResponse();
        $response->event_id = $id;
        $response->user_id = $this->user->id;
        $response->response_type_id = 1; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $response->save();

        // add to activity log
        Activity::log($event, $this->user, 6);

        Log::info('User '.$id.' is attending '.$event->name);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are now attending the event - '.$event->name,
                'Success' => view('events.single')
                    ->with(compact('event'))
                    ->with('month', '')
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are now attending the event - '.$event->name);

        return back();
    }

    /**
     * Mark user as unattending the event.
     *
     * @param $id
     *
     * @throws \Throwable
     */
    public function unattend(int $id, Request $request): Response
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        // delete the attending response
        $response = EventResponse::where('event_id', '=', $id)->where('user_id', '=', $this->user->id)->where('response_type_id', '=', 1)->first();
        $response->delete();

        // add to activity log
        Activity::log($event, $this->user, 7);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer attending the event - '.$event->name,
                'Success' => view('events.single')
                    ->with(compact('event'))
                    ->with('month', '')
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are no longer attending the event - '.$event->name);

        return back();
    }

    /**
     * Record a user's review of the event.
     *
     * @param $id
     */
    public function review(int $id, Request $request): Response
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        // add the event review
        $review = new EventReview();
        $review->event_id = $id;
        $review->user_id = $this->user->id;
        $review->review_type_id = 1; // 1 = Informational, 2 = Positive, 3 = Neutral, 4 = Negative
        $review->attended = $request->input('attended', 0);
        $review->confirmed = $request->input('confirmed', 0);
        $review->expecation = $request->input('expectation', null);
        $review->rating = $request->input('rating', null);
        $review->review = $request->input('review', null);
        $review->created_by = $this->user->id;
        $review->save();

        flash()->success('Success', 'You reviewed the event - '.$event->name);

        return back();
    }

    /**
     * Display a listing of events by tag.
     *
     * @param $tag
     *
     * @return Response
     */
    public function indexTags(Request $request, $tag)
    {
        $tag = urldecode($tag);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $future_events = Event::getByTag(ucfirst($tag))->future()
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        $future_events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        $past_events = Event::getByTag(ucfirst($tag))->past()
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->with(compact('tag'));
    }

    /**
     * Display a listing of events related to entity.
     *
     * @param $slug
     *
     * @return View
     */
    public function indexRelatedTo(Request $request, $slug)
    {
        $slug = urldecode($slug);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $future_events = Event::getByEntity(strtolower($slug))
            ->future()
            ->where(function ($query) {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        $past_events = Event::getByEntity(strtolower($slug))
            ->past()
            ->where(function ($query) {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->with(compact('slug'));
    }

    /**
     * Display a listing of events that start on the specified day.
     *
     * @param $date
     *
     * @return View
     */
    public function indexStarting(Request $request, $date)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $cdate = Carbon::parse($date);
        $cdate_yesterday = Carbon::parse($date)->subDay(1);
        $cdate_tomorrow = Carbon::parse($date)->addDay(1);

        $future_events = Event::where('start_at', '>', $cdate_yesterday->toDateString())
            ->where('start_at', '<', $cdate_tomorrow->toDateString())
            ->where(function ($query) {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('cdate'));
    }

    /**
     * Display a listing of events by venue.
     *
     * @param $slug
     *
     * @return View
     */
    public function indexVenues(Request $request, $slug)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $future_events = Event::getByVenue(strtolower($slug))
            ->future()
            ->where(function ($query) {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        $past_events = Event::getByVenue(strtolower($slug))
            ->past()
            ->where(function ($query) {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->with(compact('slug'));
    }

    /**
     * Display a listing of events by type.
     *
     * @param $slug
     *
     * @return View
     */
    public function indexTypes(Request $request, $slug)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $slug = urldecode($slug);

        $future_events = Event::getByType($slug)
            ->future()
            ->where(function ($query) {
                $query->visible($this->user);
            })
//					->orderBy('start_at', 'ASC')
            ->paginate($this->rpp);

        $past_events = Event::getByType($slug)
            ->past()
            ->where(function ($query) {
                $query->visible($this->user);
            })
            //				->orderBy('start_at', 'ASC')
            ->paginate($this->rpp);

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->with(compact('slug'));
    }

    /**
     * Display a listing of events by series.
     *
     * @param $slug
     *
     * @return View
     */
    public function indexSeries(Request $request, $slug)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $slug = urldecode($slug);

        $future_events = Event::getBySeries(strtolower($slug))
            ->future()
            ->where(function ($query) {
                $query->visible($this->user);
            })
            //->orderBy('start_at', 'ASC')
            ->paginate($this->rpp);

        $past_events = Event::getBySeries(strtolower($slug))
            ->past()
            ->where(function ($query) {
                $query->visible($this->user);
            })
            //	->orderBy('start_at', 'ASC')
            ->paginate($this->rpp);

        return view('events.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'))
            ->with(compact('slug'));
    }

    /**
     * Display a listing of events in a week view.
     *
     * @return View
     */
    public function indexWeek(Request $request)
    {
        $this->rpp = 7;

        $events = Event::future()->get();
        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.indexWeek', compact('events'));
    }

    /**
     * Add a photo to an event.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addPhoto(int $id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        $fileName = time().'_'.$request->file->getClientOriginalName();
        $filePath = $request->file('file')->storeAs('photos', $fileName, 'public');

        // get the event
        if ($event = Event::find($id)) {
            // make the photo object from the file in the request
            $photo = $this->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if ($event->photos && 0 === count($event->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to event
            $event->addPhoto($photo);
        }
    }

    /**
     * Delete a photo.
     *
     * @param int $id
     *
     * @return void
     */
    public function deletePhoto($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        $photo = $this->deletePhoto($request->file('file'));
        $photo->save();
    }

    /**
     * Mark user as following the event.
     *
     * @param $id
     *
     * @return Response | RedirectResponse
     */
    public function follow($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = 'event';
        $follow->save();

        Log::info('User '.$id.' is following '.$event->name);

        flash()->success('Success', 'You are now following the event - '.$event->name);

        return back();
    }

    /**
     * Mark user as unfollowing the event.
     *
     * @param $id
     *
     * @return Response | RedirectResponse
     */
    public function unfollow($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$event = Event::find($id)) {
            flash()->error('Error', 'No such event');

            return back();
        }

        // delete the follow
        $response = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'event')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer following the event.');

        return back();
    }

    /**
     * @return string | View
     */
    public function createSeries(Request $request)
    {
        // create a series from a single event

        $event = Event::find($request->id);

        // get a list of venues
        $venues = ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all();

        // get a list of promoters
        $promoters = ['' => ''] + Entity::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Promoter');
        })->orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $eventTypes = ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $occurrenceTypes = ['' => ''] + OccurrenceType::pluck('name', 'id')->all();
        $days = ['' => ''] + OccurrenceDay::pluck('name', 'id')->all();
        $weeks = ['' => ''] + OccurrenceWeek::pluck('name', 'id')->all();

        // initialize the form object with the values from the template
        $series = new \App\Series(['name' => $event->name,
            'slug' => $event->slug,
            'short' => $event->short,
            'venue_id' => $event->venue_id,
            'description' => $event->description,
            'event_type_id' => $event->event_type_id,
            'promoter_id' => $event->promoter_id,
            'soundcheck_at' => $event->soundcheck_at,
            'door_at' => $event->door_at,
            'founded_at' => $event->start_at,
            'start_at' => $event->start_at,
            'end_at' => $event->end_at,
            'presale_price' => $event->presale_price,
            'door_price' => $event->door_price,
            'min_age' => $event->min_age,
            'visibility_id' => $event->visibility_id,
        ]);

        return view('events.createSeries', compact('series', 'venues', 'occurrenceTypes', 'days', 'weeks', 'eventTypes', 'visibilities', 'tags', 'entities', 'promoters'))->with(['event' => $event]);
    }

    public function createThread(Request $request): RedirectResponse
    {
        // create a thread from a single event

        $event = Event::find($request->id);

        // initialize the form object with the values from the template
        $thread = new Thread([
            'forum_id' => 1,
            'name' => $event->name,
            'slug' => $event->slug,
            'description' => $event->short,
            'body' => $event->short,
            'thread_category_id' => null,
            'visibility_id' => $event->visibility_id,
            'event_id' => $event->id,
            'likes' => 0,
        ]);

        $thread->save();

        return redirect()->route('events.show', ['event' => $event->id]);
    }

    public function export(Request $request,
                            RssFeed $feed
    ) {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($filters);

        // base criteria
        $events = $this->buildCriteria($request)->take($this->rpp)->get();

        return view('events.feed', compact('events'));
    }

    public function rss(RssFeed $feed)
    {
        $rss = $feed->getRSS();

        return response($rss)
            ->header('Content-type', 'application/rss+xml');
    }

    /**
     * @param $tag
     *
     * @return mixed
     */
    public function rssTags(RssFeed $feed, $tag): Response
    {
        $rss = $feed->getTagRSS(ucfirst($tag));

        return response($rss)
            ->header('Content-type', 'application/rss+xml');
    }

    /**
     * Returns true if the user has any filters outside of the default.
     */
    protected function getIsFiltered(Request $request): bool
    {
        if (($filters = $this->getFilters($request)) == $this->getDefaultFilters()) {
            return false;
        }

        return (bool) count($filters);
    }
}
