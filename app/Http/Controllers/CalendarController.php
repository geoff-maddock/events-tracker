<?php

namespace App\Http\Controllers;

use App\Filters\EventFilters;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\Tag;
use App\Services\Calendar\CalendarRange;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            // cached like EventsController's; the entity lists are cleared by EntitiesController on save (#2173)
            'tagOptions' => ['' => '&nbsp;'] + Cache::remember('filter-opts-tags-slug', 3600, fn () => Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all()),
            'venueOptions' => ['' => ''] + Cache::remember('filter-opts-venues-name', 3600, fn () => Entity::getVenues()->pluck('name', 'name')->all()),
            'relatedOptions' => ['' => ''] + Cache::remember('filter-opts-entities-name', 3600, fn () => Entity::orderBy('name', 'ASC')->pluck('name', 'name')->all()),
            'eventTypeOptions' => ['' => ''] + Cache::remember('filter-opts-event-types-name', 3600, fn () => EventType::orderBy('name', 'ASC')->pluck('name', 'name')->all()),
        ];
    }

    /**
     * Normalize a tag filter value (string or array) into a clean array of slugs.
     *
     * @param mixed $value
     * @return array<string>
     */
    protected function normalizeTagValues(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($item) => $item !== '')
            ->values()
            ->all();
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
            ->with('visibility')
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events = $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::getByEntity(strtolower($slug))->active()->with('visibility', 'occurrenceType')->get();

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
                    'url' => '/series/'.strtolower($s->slug),
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
            ->with('visibility')
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $events = $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::getByTag($tag->name)->active()->with('visibility', 'occurrenceType')->get();

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
                    'url' => '/series/'.strtolower($s->slug),
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
    public function index(Request $request): View|JsonResponse
    {
        // FullCalendar asks this same URL (with the page's filters) for each visible
        // range; the page itself no longer inlines every event ever (#2167)
        $isFeed = $request->has('start');

        if (!$isFeed) {
            return $this->indexPage($request);
        }

        [$rangeStart, $rangeEnd] = CalendarRange::fromRequest($request);

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
                $tagValues = $this->normalizeTagValues($filters['tag']);
                if (!empty($tagValues)) {
                    $eventsQuery->whereHas('tags', function ($q) use ($tagValues) {
                        $q->whereIn('slug', $tagValues);
                    });
                }
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

            if (!empty($filters['display_type']) && $filters['display_type'] !== 'all' && $this->user) {
                $userId = $this->user->id;
                if ($filters['display_type'] === 'attending') {
                    $eventsQuery->whereIn('events.id', function ($q) use ($userId) {
                        $q->select('event_responses.event_id')
                            ->from('event_responses')
                            ->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
                            ->where('response_types.name', '=', 'Attending')
                            ->where('event_responses.user_id', '=', $userId);
                    });
                } elseif ($filters['display_type'] === 'not_attending') {
                    $eventsQuery->whereNotIn('events.id', function ($q) use ($userId) {
                        $q->select('event_responses.event_id')
                            ->from('event_responses')
                            ->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
                            ->where('response_types.name', '=', 'Attending')
                            ->where('event_responses.user_id', '=', $userId);
                    });
                } elseif ($filters['display_type'] === 'created') {
                    $eventsQuery->where('events.created_by', '=', $userId);
                } elseif ($filters['display_type'] === 'not_created') {
                    $eventsQuery->where('events.created_by', '!=', $userId);
                }
            }
        }

        // events in the requested range, with filters applied
        $events = $eventsQuery->whereBetween('events.start_at', [$rangeStart, $rangeEnd])
            ->with('eventType', 'visibility')
            ->get();

        // get all the upcoming series events
        // eager-load upcomingEvent so Series::nextEvent() uses the loaded relation
        // instead of issuing a query per series (Sentry N+1 on /calendar)
        $seriesQuery = Series::active()->with('visibility', 'occurrenceType', 'upcomingEvent');
        
        // Apply same filters to series if present
        if ($request->has('filters')) {
            $filters = $request->get('filters');
            
            if (!empty($filters['name'])) {
                $seriesQuery->where('series.name', 'like', '%' . $filters['name'] . '%');
            }
            
            if (!empty($filters['tag'])) {
                $tagValues = $this->normalizeTagValues($filters['tag']);
                if (!empty($tagValues)) {
                    $seriesQuery->whereHas('tags', function ($q) use ($tagValues) {
                        $q->whereIn('slug', $tagValues);
                    });
                }
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

        // adds the next scheduled instance of each series that falls in the range
        foreach ($series as $s) {
            if (null !== $s->nextEvent()) {
                continue;
            }

            // one walk through the occurrence cycle per series (nextOccurrenceEndDate() repeats it)
            $next = $s->nextOccurrenceDate();

            if (null === $next || !$next->between($rangeStart, $rangeEnd)) {
                continue;
            }

            $eventList[] = [
                'id' => 'series-'.$s->id,
                'start' => $next->format('Y-m-d H:i'),
                'end' => $next->copy()->addHours((int) $s->length)->format('Y-m-d H:i'),
                'title' => $s->name,
                'url' => '/series/'.strtolower($s->slug),
                'backgroundColor' => '#99bcdb',
                'description' => $s->short,
            ];
        }

        return response()->json($eventList);
    }

    /**
     * The calendar page; events are fetched per visible range from index()'s JSON feed.
     */
    private function indexPage(Request $request): View
    {
        // set the initial date of the calendar that is displayed
        $initialDate = Carbon::now()->format('Y-m-d');

        // the feed is this URL with the same filters; FullCalendar appends start/end
        $calendarFeedUrl = $request->fullUrlWithoutQuery(['start', 'end', 'timeZone']);

        $filters = $request->get('filters', []);
        $effectiveFilters = array_filter($filters, function ($value, $key) {
            if ($key === 'display_type' && $value === 'all') {
                return false;
            }
            return !empty($value);
        }, ARRAY_FILTER_USE_BOTH);
        $hasFilter = !empty($effectiveFilters);

        return view('events.event-calendar-tw', compact('calendarFeedUrl', 'initialDate', 'filters', 'hasFilter'))
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
        })->with('eventType')->get();

        // get all the upcoming series events
        $series = Series::active()->with('visibility', 'occurrenceType', 'upcomingEvent')->get();

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
                    'url' => '/series/'.strtolower($s->slug),
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
        //             'url' => '/series/'.strtolower($s->slug),
        //             'backgroundColor' => '#99bcdb',
        //             'description' => $s->short,
        //         ];
        //     }
        // }

        $eventList = json_encode($eventList);

        return view('events.dynamic-tag-event-calendar-tw', compact('eventList'));
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
                    'url' => '/series/'.strtolower($s->slug),
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
                    'url' => '/series/'.strtolower($s->slug),
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
                    'url' => '/series/'.strtolower($s->slug),
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

        $events = $events->filter(function ($e) {
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
                    'url' => '/series/'.strtolower($s->slug),
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar-tw', compact('eventList', 'slug','initialDate'));
    }
 
}
