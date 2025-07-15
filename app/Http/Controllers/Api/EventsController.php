<?php

namespace App\Http\Controllers\Api;

use App\Events\EventCreated;
use App\Events\EventUpdated;
use App\Filters\EventFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Mail\FollowingUpdate;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventType;
use App\Models\Follow;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\ResponseType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\Visibility;
use App\Notifications\EventPublished;
use App\Services\Embeds\EmbedExtractor;
use App\Services\ImageHandler;
use App\Services\RssFeed;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use FacebookAds\Api as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Storage;

class EventsController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected int $defaultGridLimit;

    protected int $gridLimit;

    // this should be an array of filter values
    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected EventFilters $filter;

    protected bool $hasFilter;

    protected int $defaultWindow;

    protected Api $facebook;

    public function __construct(EventFilters $filter)
    {
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.events.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultGridLimit = 24;
        $this->defaultSort = 'start_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultWindow = 4;

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->gridLimit = 24;

        $this->defaultSortCriteria = ['events.start_at' => 'desc'];

        $this->hasFilter = false;

        // this is causing issues in some places, but do we need it?
        // $this->middleware('auth:sanctum');
        $this->middleware('auth:sanctum')->only(['attendJson', 'unattendJson','store']);

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('api_event');
        $listParamSessionStore->setKeyPrefix('api_event_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Event::query()
                    ->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')
                    ->leftJoin('entities as venue', 'events.venue_id', '=', 'venue.id')
                    ->leftJoin('entities as promoter', 'events.promoter_id', '=', 'promoter.id')
                    ->select('events.*')
        ;

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        // @phpstan-ignore-next-line
        $events = $query->visible($this->user)
            ->with([
                'visibility',
                'venue',
                'eventStatus',
                'eventType',
                'promoter',
                'series',
                'tags',
                'entities',
            ])
            ->paginate($listResultSet->getLimit());
    
        return response()->json(new EventCollection($events));
    }

    /**
     * Display a listing of events by date.
     *
     * @throws \Throwable
     */
    public function indexByDate(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $year,
        ?string $month = null,
        ?string $day = null
    ): JsonResponse {
        // set the start_at from and to dates based on the passed params
        if ($year && !$month && !$day) {
            $start_at_from = $year.'0101';
            $start_at_to = $year.'1231';
            $slug = $year;
        } elseif (!$day) {
            $start_at_from = Carbon::parse($year.$month.'01');
            $start_at_to = Carbon::parse($start_at_from)->endOfMonth();
            $slug = $year.' - '.$month;
        } else {
            $start_at_from = Carbon::parse($year.$month.$day);
            $start_at_to = Carbon::parse($start_at_from)->addDay();
            $slug = $year.' - '.$month.' - '.$day;
        }

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Event::query()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        $events = Event::where('start_at', '>', $start_at_from)
            ->where('start_at', '<', $start_at_to)
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->with([
                'visibility',
                'venue',
                'eventStatus',
                'eventType',
                'promoter',
                'series',
                'tags',
                'entities',
            ])
            ->paginate($listResultSet->getLimit());
        

        return response()->json(new EventCollection($events));
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['events.name' => 'Name', 'events.start_at' => 'Start At', 'event_types.name' => 'Event Type', 'events.updated_at' => 'Updated At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
            'venueOptions' => ['' => ''] + Entity::getVenues()->pluck('name', 'name')->all(),
            'relatedOptions' => ['' => ''] + Entity::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'eventTypeOptions' => ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'venueOptions' => ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all(),
            'promoterOptions' => ['' => ''] + Entity::whereHas('roles', function ($q) {
                $q->where('name', '=', 'Promoter');
            })->orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'eventTypeOptions' => ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'seriesOptions' => ['' => ''] + Series::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'tagOptions' => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityOptions' => Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'userOptions' => ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }



    /**
     * Reset the limit, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_event_index';
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('events.index');
    }


    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): JsonResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_event_index';
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return response()->json([]);
    }


    /**
     * Get the events for one passed day.
     *
     * @return Response|string
     *
     * @throws \Throwable
     */
    public function day(string $day)
    {
        if (!$day) {
            flash()->error('Error', 'No such day');

            return back();
        }
        $day = Carbon::parse($day);

        return view('events.day')
            ->with([
                'day' => $day,
                'position' => 0,
                'offset' => 0,
            ])
            ->render();
    }

 
    /**
     * Display a listing of events related to entity.
     */
    public function calendarRelatedTo(Request $request, string $slug): View
    {
        // get the entity by the slug name
        $related = Entity::where('slug', '=', $slug)->firstOrFail();

        // get all events related to the entity
        $events = Event::getByEntity(strtolower($slug))
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::getByEntity(strtolower($slug))->active()->get();

        $series = $series->filter(function ($e) {
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        $eventList = [];

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->id,
                'backgroundColor' => '#0a57ad',
                'description' => $event->short,
            ];
        }

        // adds series to events list
        foreach ($series as $s) {
            if (null === $s->nextEvent() && null !== $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = [
                    'id' => 'series-'.$s->id,
                    'start' => $s->nextOccurrenceDate()->format('Y-m-d H:i'),
                    'end' => ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null),
                    'title' => $s->name,
                    'url' => '/series/'.$s->id,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        // converts array of events into json event list
        $eventList = json_encode($eventList);

        return view('events.event-calendar', compact('eventList', 'related'));
    }

    /**
     * Display a listing of events by tag.
     */
    public function calendarTags(string $slug): View
    {
        // get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $eventList = [];

        $events = Event::getByTag($tag->name)
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::getByTag($tag->name)->active()->get();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->id,
                'backgroundColor' => '#0a57ad',
                'description' => $event->short,
            ];
        }

        // adds series to events list
        foreach ($series as $s) {
            if (null === $s->nextEvent() && null !== $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = [
                    'id' => 'series-'.$s->id,
                    'start' => $s->nextOccurrenceDate()->format('Y-m-d H:i'),
                    'end' => ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null),
                    'title' => $s->name,
                    'url' => '/series/'.$s->id,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        // converts array of events into json event list
        $eventList = json_encode($eventList);

        return view('events.event-calendar', compact('eventList', 'tag'));
    }

    /**
     * Display a calendar view of events.
     **/
    public function calendar(): View
    {
        $eventList = [];

        // get all public events
        $events = Event::where(function ($query) {
            /* @phpstan-ignore-next-line */
            $query->visible($this->user);
        })->get();

        // get all the upcoming series events
        $series = Series::active()->get();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by === $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->id,
                'backgroundColor' => $event->eventType->backgroundColor(),
                'description' => $event->short,
            ];
        }

        // adds series to events list
        foreach ($series as $s) {
            if (null === $s->nextEvent() && null !== $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = [
                    'id' => 'series-'.$s->id,
                    'start' => $s->nextOccurrenceDate()->format('Y-m-d H:i'),
                    'end' => ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null),
                    'title' => $s->name,
                    'url' => '/series/'.$s->id,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar', compact('eventList'));
    }

 

    /**
     * API endpoint for calendar-events that collects events and series and returns json.
     */
    public function calendarEventsApi(Request $request): JsonResponse
    {
        // build the json results to return which include both series and events
        $eventList = [];

        // get the query params from
        $start = $request->query('start', Carbon::now()->startOfMonth());
        $end = $request->query('end', Carbon::now()->endOfMonth());

        // get all public events
        $events = Event::where('start_at', '>=', $start)
            ->where('start_at', '<=', $end)
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })->get();

        // get all the upcoming series events
        $series = Series::active()->get();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by === $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->id,
                'backgroundColor' => '#0a57ad',
                'description' => $event->short,
            ];
        }

        // adds series to events list
        foreach ($series as $s) {
            if (null === $s->nextEvent() && null !== $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = [
                    'id' => 'series-'.$s->id,
                    'start' => $s->nextOccurrenceDate()->format('Y-m-d H:i'),
                    'end' => ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null),
                    'title' => $s->name,
                    'url' => '/series/'.$s->id,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        // converts array of events into json event list
        return response()->json($eventList);
    }

    /**
     * API endpoint for calendar-events that collects events and series and returns json.
     */
    public function tagCalendarEventsApi(Request $request): JsonResponse
    {
        // build the json results to return which include both series and events
        $eventList = [];

        // get the query params from
        $start = $request->query('start', Carbon::now()->startOfMonth());
        $end = $request->query('end', Carbon::now()->endOfMonth());

        // get all public events
        $events = Event::where('start_at', '>=', $start)
            ->where('start_at', '<=', $end)
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })->get();

        // get all the upcoming series events
        $series = Series::active()->get();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by === $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->tagNames,
                'url' => '/events/'.$event->id,
                'backgroundColor' => '#0a57ad',
                'description' => $event->short,
            ];
        }

        // adds series to events list
        foreach ($series as $s) {
            if (null === $s->nextEvent() && null !== $s->nextOccurrenceDate()) {
                // add the next instance of each series to the calendar
                $eventList[] = [
                    'id' => 'series-'.$s->id,
                    'start' => $s->nextOccurrenceDate()->format('Y-m-d H:i'),
                    'end' => ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null),
                    'title' => $s->tagNames,
                    'url' => '/series/'.$s->id,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        // converts array of events into json event list
        return response()->json($eventList);
    }


    public function show(?Event $event, EmbedExtractor $embedExtractor): JsonResponse
    {
        if (!$event) {
            abort(404);
        }

        $thread = Thread::where('event_id', '=', $event->id)->first();

        // check blacklist status
        $blacklist = $this->checkBlackList($event);

        // extract all the links from the event body and convert into embeds
        $embeds = $embedExtractor->getEmbedsForEvent($event);

        return response()->json(new EventResource($event));
    }


    public function embeds(?Event $event,  EmbedExtractor $embedExtractor): JsonResponse
    {
        if (!$event) {
            abort(404);
        }

        // extract all the links from the event body and convert into embeds
        $embedList = $embedExtractor->getEmbedsForEvent($event);

        // create a paginated list of embeds, but for now just using one page
        $embeds = [
            'data' => $embedList,
            'total' => count($embedList),
            'current_page' => 1,
            'per_page' => 100,
            'first_page_url' => '/events/'.$event->slug.'/embeds',
            'from' => 1,
            'last_page' => 1,
            'next_page_url' => '/events/'.$event->slug.'/embeds',
            'path' => '/events/'.$event->slug.'/embeds',
            'prev_page_url' => '/events/'.$event->slug.'/embeds',
            'to' => count($embedList),
        ];

        
        // converts array of embeds into json embed list
        return response()->json($embeds);
    }

    public function minimalEmbeds(?Event $event,  EmbedExtractor $embedExtractor): JsonResponse
    {
        if (!$event) {
            abort(404);
        }

        // extract all the links from the event body and convert into embeds
        $embedExtractor->setLayout("small");
        $embedList = $embedExtractor->getEmbedsForEvent($event);

        // create a paginated list of embeds, but for now just using one page
        $embeds = [
            'data' => $embedList,
            'total' => count($embedList),
            'current_page' => 1,
            'per_page' => 100,
            'first_page_url' => '/events/'.$event->slug.'/minimal-embeds',
            'from' => 1,
            'last_page' => 1,
            'next_page_url' => '/events/'.$event->slug.'/minimal-embeds',
            'path' => '/events/'.$event->slug.'/minimal-embeds',
            'prev_page_url' => '/events/'.$event->slug.'/minimal-embeds',
            'to' => count($embedList),
        ];

        
        // converts array of embeds into json embed list
        return response()->json($embeds);
    }

    public function store(EventRequest $request, Event $event): JsonResponse
    {
        // check who the authenticated user is
        $this->user = $request->user();

        $msg = '';

        $input = $request->all();

        // transform the slug passed in the request
        $input['slug'] = Str::slug($request->input('slug', '-'));
        
        // Set the user fields explicitly
        $input['created_by'] = $this->user->id;
        $input['updated_by'] = $this->user->id;

        // validation happening in EventRequest->rules
        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
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

        // add to activity log
        Activity::log($event, $this->user, 1);

        // dispatch notifications that the event was created
        EventCreated::dispatch($event);

        $photo = $event->getPrimaryPhoto();

        // make a call to notify all users who are following any of the tags/keywords if the event starts in the future
        if ($event->start_at >= Carbon::now()) {
            // only do the notification if there is a photo
            if ($photo !== null) {
                $this->notifyFollowing($event);
            }
        }

        return response()->json(new EventResource($event));
    }

    protected function notifyFollowing(Event $event): void
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $event->tags()->get();
        $users = [];

        // improve this so it will only send one email to each user per event, and include a list of all tags they were following that led to the notification
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user->profile && $user->profile->setting_instant_update !== 1) {
                    continue;
                }

                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->user_id, $users)) {
                    Mail::to($user->email)
                        ->send(new FollowingUpdate($url, $site, $admin_email, $reply_email, $user, $event, $tag));
                    $users[$user->user_id] = $tag->name;
                } else {
                    $users[$user->user_id] = $users[$user->user_id].', '.$tag->name;
                }
            }
        }

        // notify users following any of the entities
        $entities = $event->entities()->get();

        // improve this so it will only sent one email to each user per event, and include a list of entities they were following that led to the notification
        foreach ($entities as $entity) {
            foreach ($entity->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user->profile && $user->profile->setting_instant_update !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::to($user->email)
                        ->send(new FollowingUpdate($url, $site, $admin_email, $reply_email, $user, $event, $entity));
                    $users[$user->id] = $entity->name;
                } else {
                    $users[$user->id] = $users[$user->id].', '.$entity->name;
                }
            }
        }
    }

    public function update(Event $event, EventRequest $request): JsonResponse
    {
        // Get the authenticated user
        $this->user = $request->user();

        if (!$event->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $msg = '';

        $input = $request->input();
        $input['updated_by'] = $this->user->id;
        
        $event->fill($input)->save();

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
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

        return response()->json(new EventResource($event));
    }

    protected function unauthorized(EventRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Event $event): JsonResponse
    {
        // add to activity log
        Activity::log($event, $this->user, 3);

        $event->delete();

        return response()->json([], 204);
    }

    /**
     * Tweet this event.
     *
     * @throws \Throwable
     */
    public function tweet(int $id): RedirectResponse
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
     * @throws \Throwable
     */
    public function attend(int $id, Request $request): RedirectResponse | array
    {
        $this->middleware('auth:sanctum');

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
        $response->event()->associate($id);
        $response->user()->associate($this->user);
        $response->responseType()->associate(ResponseType::find(1)); // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $response->save();

        // add to activity log
        Activity::log($event, $this->user, 6);

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
     * @throws \Throwable
     */
    public function unattend(int $id, Request $request): RedirectResponse | array
    {
        $this->middleware('auth:sanctum');

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
     * Mark the authenticated user as attending an event and return JSON.
     */
    public function attendJson(Event $event, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $response = EventResponse::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('response_type_id', 1)
            ->first();

        if (!$response) {
            $response = new EventResponse();
            $response->event()->associate($event);
            $response->user()->associate($user);
            $response->response_type_id = 1;
            $response->save();

            Activity::log($event, $user, 6);
        }

        return response()->json(new EventResource($event));
    }

    /**
     * Remove the authenticated user's attendance from an event and return JSON.
     */
    public function unattendJson(Event $event, Request $request): JsonResponse
    {
        $user = $request->user();

        $response = EventResponse::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('response_type_id', 1)
            ->first();

        if ($response) {
            $response->delete();
            Activity::log($event, $user, 7);
        }

        return response()->json([], 204);
    }


    /**
     * Display a listing of events that start on the specified day.
     *
     * @return View
     */
    public function indexStarting(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $date
    ) {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_starting');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder(Event::query())
            ->setDefaultSort(['events.start_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        $cdate = Carbon::parse($date);
        $cdate_yesterday = Carbon::parse($date)->subDay();
        $cdate_tomorrow = Carbon::parse($date)->addDay();

        $future_events = Event::where('events.start_at', '>', $cdate_yesterday->toDateString())
            ->where('events.start_at', '<', $cdate_tomorrow->toDateString())
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.index')
            ->with(
                array_merge(
                    [
                        'limit' => $listResultSet->getLimit(),
                        'sort' => $listResultSet->getSort(),
                        'direction' => $listResultSet->getSortDirection(),
                        'hasFilter' => $this->hasFilter,
                        'filters' => $listResultSet->getFilters(),
                    ],
                    $this->getFilterOptions(),
                    $this->getListControlOptions()
                )
            )
            ->with(compact('future_events'))
            ->with(compact('cdate'));
    }

    /**
     * Display a listing of events in a week view.
     *
     * @return View
     */
    public function indexWeek(Request $request)
    {
        // no filters or sorting applied, just future events
        $events = Event::future()->get();
        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('events.indexWeek', compact('events'));
    }

    public function follow(int $id): RedirectResponse
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

    public function unfollow(int $id): RedirectResponse
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

    protected function getSeriesFormOptions(): array
    {
        return [
            'venueOptions' => ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all(),
            'promoterOptions' => ['' => ''] + Entity::whereHas('roles', function ($q) {
                $q->where('name', '=', 'Promoter');
            })->orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'eventTypeOptions' => ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'tagOptions' => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityOptions' => Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'occurrenceTypeOptions' => ['' => ''] + OccurrenceType::pluck('name', 'id')->all(),
            'dayOptions' => ['' => ''] + OccurrenceDay::pluck('name', 'id')->all(),
            'weekOptions' => ['' => ''] + OccurrenceWeek::pluck('name', 'id')->all(),
            'userOptions' => User::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * @return string|View
     */
    public function createSeries(Request $request)
    {
        // create a series from a single event

        $event = Event::find($request->id);

        // initialize the form object with the values from the template
        $series = new Series(
            [
                'name' => $event->name,
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
                'length' => null,
            ]
        );

        return view('events.createSeries', compact('series'))
        ->with($this->getSeriesFormOptions())
        ->with(['event' => $event]);
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
            'tag',
        ]);

        $thread->save();

        $thread->tags()->attach($event->tags()->pluck('tags.id')->toArray());
        $thread->entities()->attach($event->entities()->pluck('entities.id')->toArray());

        return redirect()->route('events.show', ['event' => $event->id]);
    }

    public function export(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        RssFeed $feed
    ): View {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Event::query()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        /* @phpstan-ignore-next-line */
        $events = $query->visible($this->user)
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        return view('events.feed', compact('events'));
    }

    public function rss(RssFeed $feed): Response
    {
        $rss = $feed->getRSS();

        return response($rss)
            ->header('Content-type', 'application/rss+xml');
    }

    public function rssTags(RssFeed $feed, string $tag): Response
    {
        $rss = $feed->getTagRSS(ucfirst($tag));

        return response($rss)
            ->header('Content-type', 'application/rss+xml');
    }

    protected function checkBlackList(?Event $event): bool
    {
        $blacklist = false;

        // blacklist events that have venues in the blacklist
        if (!empty($event->venue_id)) {
            if ($blacklistConfig = config('app.spider_blacklist')) {
                $blacklistArray = explode(',', $blacklistConfig);
                foreach ($blacklistArray as $item) {
                    if (strtolower($item) == strtolower($event->venue->name)) {
                        $blacklist = true;
                    }
                }
            }
        }

        return $blacklist;
    }

    public function photos(?Event $event): JsonResponse
    {
        if (!$event) {
            abort(404);
        }

        $photos = [];

        // extract all the links from the event
        $photoList = $event->photos()->get();

        foreach ($photoList as $photo) {
            $photos[] = [
                'id' => $photo->id,
                'name' => $photo->name,
                'path' => Storage::disk('external')->url($photo->getStoragePath()),
                'thumbnail_path' => Storage::disk('external')->url($photo->getStorageThumbnail())
            ];
        }

        return response()->json($photos);
    }

    public function allPhotos(?Event $event): JsonResponse
    {
        if (!$event) {
            abort(404);
        }

        $photos = [];

        $photoList = $event->photos()->get();
        foreach ($photoList as $photo) {
            $photos[$photo->id] = [
                'id' => $photo->id,
                'name' => $photo->name,
                'path' => Storage::disk('external')->url($photo->getStoragePath()),
                'thumbnail_path' => Storage::disk('external')->url($photo->getStorageThumbnail()),
            ];
        }

        $entities = $event->entities()->with('photos')->get();
        foreach ($entities as $entity) {
            foreach ($entity->photos as $photo) {
                $photos[$photo->id] = [
                    'id' => $photo->id,
                    'name' => $photo->name,
                    'path' => Storage::disk('external')->url($photo->getStoragePath()),
                    'thumbnail_path' => Storage::disk('external')->url($photo->getStorageThumbnail()),
                ];
            }
        }

        return response()->json(array_values($photos));
    }

    /**
     * Add a photo to an event.
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): JsonResponse
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp',
        ]);

        if ($event = Event::find($id)) {
            $photo = $imageHandler->makePhoto($request->file('file'));

            if (isset($event->photos) && 0 === count($event->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();
            $event->addPhoto($photo);

            if ($event->start_at >= Carbon::now()) {
                if (1 === count($event->photos)) {
                    $this->notifyFollowing($event);
                }
            }

            $photoData = [
                'id' => $photo->id,
                'name' => $photo->name,
                'path' => Storage::disk('external')->url($photo->getStoragePath()),
                'thumbnail_path' => Storage::disk('external')->url($photo->getStorageThumbnail()),
            ];

            return response()->json($photoData, 201);
        }

        return response()->json([], 404);
    }
}
