<?php

namespace App\Http\Controllers;

use App\Filters\EventFilters;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;


class CalendarController extends Controller
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

    public function __construct(EventFilters $filter)
    {
        $this->middleware('verified', ['only' => ['create', 'edit', 'duplicate','store', 'update', 'indexAttending', 'calendarAttending']]);
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
        parent::__construct();
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
     * Filter the list of events.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        // list entity result builder
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

        // nothing really happens until here in cadence
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        // @phpstan-ignore-next-line
        $events = $query->visible($this->user)
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.index-tw')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('events'))
            ->render();
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

        return redirect()->route($request->get('redirect') ?? 'events.index');
    }

    /**
     * Reset the filtering of entities.
     *
     * @return RedirectResponse|View
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ) {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_event_index';
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'events.index');
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

        return view('events.day-tw')
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
        $initialDate = Carbon::now()->format('Y-m-d');

        $eventList = [];

        // get all events related to the entity
        // \PHPStan\dumpType(Event::getByEntity(strtolower($slug))->get());
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

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        // converts array of events into json event list
        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'related', 'initialDate'));
    }

    /**
     * Display a listing of events by tag.
     */
    public function calendarTags(string $slug): View
    {
        // get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $initialDate = Carbon::now()->format('Y-m-d');

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
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        // converts array of events into json event list
        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'tag', 'initialDate'));
    }

    /**
     * Display a calendar view of events.
     **/
    public function index(Request $request): View
    {
        // set the initial date of the calendar that is displayed
        $initialDate = Carbon::now()->format('Y-m-d');

        $eventList = [];

        // Start with base query
        $eventsQuery = Event::query()->where(function ($query) {
            /* @phpstan-ignore-next-line */
            $query->visible($this->user);
        });

        // Apply filters if present
        if ($request->has('filters')) {
            $filters = $request->get('filters');
            
            if (!empty($filters['name'])) {
                $eventsQuery->where('events.name', 'like', '%' . $filters['name'] . '%');
            }
            
            if (!empty($filters['tag'])) {
                $eventsQuery->whereHas('tags', function ($q) use ($filters) {
                    $q->where('slug', $filters['tag']);
                });
            }
            
            if (!empty($filters['venue'])) {
                $eventsQuery->whereHas('venue', function ($q) use ($filters) {
                    $q->where('name', $filters['venue']);
                });
            }
            
            if (!empty($filters['related'])) {
                $eventsQuery->whereHas('entities', function ($q) use ($filters) {
                    $q->where('name', $filters['related']);
                });
            }
            
            if (!empty($filters['event_type'])) {
                $eventsQuery->whereHas('eventType', function ($q) use ($filters) {
                    $q->where('name', $filters['event_type']);
                });
            }
        }

        // get all public events with filters applied
        $events = $eventsQuery->with('eventType', 'visibility')->get();

        // get all the upcoming series events
        $seriesQuery = Series::active()->with('visibility','occurrenceType');
        
        // Apply same filters to series if present
        if ($request->has('filters')) {
            $filters = $request->get('filters');
            
            if (!empty($filters['name'])) {
                $seriesQuery->where('series.name', 'like', '%' . $filters['name'] . '%');
            }
            
            if (!empty($filters['tag'])) {
                $seriesQuery->whereHas('tags', function ($q) use ($filters) {
                    $q->where('slug', $filters['tag']);
                });
            }
            
            if (!empty($filters['venue'])) {
                $seriesQuery->whereHas('venue', function ($q) use ($filters) {
                    $q->where('name', $filters['venue']);
                });
            }
            
            if (!empty($filters['related'])) {
                $seriesQuery->whereHas('entities', function ($q) use ($filters) {
                    $q->where('name', $filters['related']);
                });
            }
        }
        
        $series = $seriesQuery->get();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            return (
                ('Public' == $e->visibility->name) ||
                 ($this->user && $e->created_by === $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);
        
        $filters = $request->get('filters', []);
        $hasFilter = !empty(array_filter($filters));

        return view('events.event-calendar-tw', compact('eventList', 'initialDate', 'filters', 'hasFilter'))
            ->with($this->getFilterOptions());
    }

    /**
     * Display a calendar view of events by date
     **/
    public function indexByDate(
        ?string $year = null,
        ?string $month = null): View
    {
        // set the initial date of the calendar that is displayed
        $year = isset($year) ? $year : Carbon::now()->year;
        $month = isset($month) ? $month : Carbon::now()->format('m');
        $day = '01';

        $initialDate = $year.'-'.$month.'-'.$day;

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
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'initialDate'));
    }

    /**
     * Display a calendar view of events but only display the related tags.
     **/
    public function calendarTagOnly(): View
    {
        $eventList = [];

        // // get all public events
        // $events = Event::where(function ($query) {
        //     /* @phpstan-ignore-next-line */
        //     $query->visible($this->user);
        // })->get();

        // // get all the upcoming series events
        // $series = Series::active()->get();

        // // filter for only events that are public or that were created by the current user and are not "no schedule"
        // $series = $series->filter(function ($e) {
        //     return (('Public' == $e->visibility->name) || ($this->user && $e->created_by === $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        // });

        // // adds events to event list
        // foreach ($events as $event) {
        //     $eventList[] = [
        //         'id' => 'event-'.$event->id,
        //         'start' => $event->start_at->format('Y-m-d H:i'),
        //         'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
        //         'title' => $event->tagNames,
        //         'url' => '/events/'.$event->slug,
        //         'backgroundColor' => $event->eventType->backgroundColor(),
        //         'description' => $event->short,
        //     ];
        // }

        // // adds series to events list
        // foreach ($series as $s) {
        //     if (null === $s->nextEvent() && null !== $s->nextOccurrenceDate()) {
        //         // add the next instance of each series to the calendar
        //         $eventList[] = [
        //             'id' => 'series-'.$s->id,
        //             'start' => $s->nextOccurrenceDate()->format('Y-m-d H:i'),
        //             'end' => ($s->nextOccurrenceEndDate() ? $s->nextOccurrenceEndDate()->format('Y-m-d H:i') : null),
        //             'title' => $s->tagNames,
        //             'url' => '/series/'.$s->slug,
        //             'backgroundColor' => '#99bcdb',
        //             'description' => $s->short,
        //         ];
        //     }
        // }

        $eventList = json_encode($eventList);

        return view('events.dynamic-tag-event-calendar-tw', compact('eventList'));
    }

    /**
     * Displays the calendar based on passed events and tag.
     *
     * @param array|null $series
     * @param null       $tag
     */
    public function renderCalendar(Collection $events, $series = null, $tag = null): View
    {
        // Change this to instead pass in the json EventsList directly here and render, that way I can just pass anything to this function to display the calendar
        return view('events.event-calendar-tw');
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
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
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
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        // converts array of events into json event list
        return response()->json($eventList);
    }

    /**
     * Display a calendar view of events you are attending.
     *
     * @return view
     **/
    public function calendarAttending()
    {
        $this->middleware('auth');
        $initialDate = Carbon::now()->format('Y-m-d');

        $eventList = [];

        $events = $this->user->getAttending()
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        // filter events that are public or created by the logged in user
        $events = $events->filter(function ($e) {
            /* @phpstan-ignore-next-line */
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Series[] $series */
        $series = $this->user->getSeriesFollowing();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            /** @var \App\Models\Series $e */
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        $slug = 'Attending';

        // adds events to event list
        foreach ($events as $event) {
            // @phpstan-ignore-next-line
            $eventList[] = [
            // @phpstan-ignore-next-line
                'id' => 'event-'.$event->id,
            // @phpstan-ignore-next-line
                'start' => $event->start_at->format('Y-m-d H:i'),
            // @phpstan-ignore-next-line
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
            // @phpstan-ignore-next-line
                'title' => $event->name,
            // @phpstan-ignore-next-line
                'url' => '/events/'.$event->slug,
                'backgroundColor' => '#0a57ad',
            // @phpstan-ignore-next-line
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'slug', 'initialDate'));
    }

    /**
     * Display a calendar view of free events.
     *
     * @return view
     **/
    public function calendarFree()
    {
        $eventList = [];

        $initialDate = Carbon::now()->format('Y-m-d');

        $events = Event::where('door_price', 0)
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->with('visibility', 'eventType')
            ->get();

        // filter public events and those created by the current user
        $events = $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::where('door_price', 0)->active()->with('visibility','occurrenceType')->get();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        $slug = 'No Cover';

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'slug','initialDate'));
    }

    /**
     * Display a calendar view of all ages.
     *
     * @return view
     */
    public function calendarMinAge(int $age)
    {
        $eventList = [];
        $initialDate = Carbon::now()->format('Y-m-d');

        $events = Event::where('min_age', '<=', $age)
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->with('visibility','eventType')
            ->get();

        // filter only public events and those created by the user
        $events = $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::where('min_age', '<=', $age)->active()->with('visibility','occurrenceType')->get();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
            return (('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id)) and 'No Schedule' != $e->occurrenceType->name;
        });

        $slug = 'Min Age '.$age;

        // adds events to event list
        foreach ($events as $event) {
            $eventList[] = [
                'id' => 'event-'.$event->id,
                'start' => $event->start_at->format('Y-m-d H:i'),
                'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
                'title' => $event->name,
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'slug','initialDate'));
    }

    /**
     * Display a listing of events by event type.
     */
    public function calendarEventTypes(string $type): View
    {
        // $tag = urldecode($type);
        $slug = Str::title(str_replace('-', ' ', $type));

        $eventList = [];

        $initialDate = Carbon::now()->format('Y-m-d');

        $events = Event::getByType($slug)
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->with('visibility','eventType')
            ->get();

        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::getByType($slug)->active()->with('visibility','occurrenceType')->get();

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
                'url' => '/events/'.$event->slug,
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
                    'url' => '/series/'.$s->slug,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'slug','initialDate'));
    }
 
}
