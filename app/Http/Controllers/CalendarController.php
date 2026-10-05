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
use Illuminate\Support\Collection;
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
     * Series anyone may see, plus the viewer's own, that have a schedule.
     *
     * @param Collection<int, Series> $series
     *
     * @return Collection<int, Series>
     */
    private function shownSeries(Collection $series): Collection
    {
        return $series->filter(
            fn (Series $s) => ('Public' == $s->visibility->name || ($this->user && $s->created_by == $this->user->id))
                && 'No Schedule' != $s->occurrenceType->name
        );
    }

    /**
     * FullCalendar's item for an event, coloured by its type.
     *
     * @return array<string, mixed>
     */
    private function eventItem(Event $event): array
    {
        return [
            'id' => 'event-'.$event->id,
            'start' => $event->start_at->format('Y-m-d H:i'),
            'end' => ($event->end_time !== null) ? $event->end_time->format('Y-m-d H:i') : null,
            'title' => $event->name,
            'url' => '/events/'.$event->slug,
            'backgroundColor' => $event->eventType->backgroundColor(),
            'description' => $event->short,
        ];
    }

    /**
     * FullCalendar items for the next scheduled instance of each series that
     * has no event for it yet and starts in [$from, $to].
     *
     * @param iterable<Series> $series
     *
     * @return array<int, array<string, mixed>>
     */
    private function seriesItems(iterable $series, Carbon $from, Carbon $to): array
    {
        $items = [];
        foreach ($series as $s) {
            if (null !== $s->nextEvent()) {
                continue;
            }

            // one walk through the occurrence cycle per series (nextOccurrenceEndDate() repeats it)
            $next = $s->nextOccurrenceDate();
            if (null === $next || !$next->between($from, $to)) {
                continue;
            }

            $items[] = [
                'id' => 'series-'.$s->id,
                'start' => $next->format('Y-m-d H:i'),
                'end' => $next->copy()->addHours((int) $s->length)->format('Y-m-d H:i'),
                'title' => $s->name,
                'url' => '/series/'.strtolower($s->slug),
                'backgroundColor' => '#99bcdb',
                'description' => $s->short,
            ];
        }

        return $items;
    }

    /**
     * The feed filters events and series share: name, tag, venue, related
     * entity (by name or slug), event type, free entry and minimum age.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Event>|\Illuminate\Database\Eloquent\Builder<Series> $query
     * @param array<string, mixed> $filters
     */
    private function applyFeedFilters($query, array $filters, string $table): void
    {
        if (!empty($filters['name'])) {
            $query->where($table.'.name', 'like', '%'.$filters['name'].'%');
        }

        if (!empty($filters['tag'])) {
            $tagValues = $this->normalizeTagValues($filters['tag']);
            if (!empty($tagValues)) {
                $query->whereHas('tags', fn ($q) => $q->whereIn('slug', $tagValues));
            }
        }

        if (!empty($filters['venue'])) {
            $query->whereHas('venue', fn ($q) => $q->where('name', $filters['venue']));
        }

        if (!empty($filters['related'])) {
            $query->whereHas('entities', fn ($q) => $q->where('name', $filters['related']));
        }

        // an entity by slug (names aren't unique), for /calendar/related-to/{slug}
        if (!empty($filters['entity'])) {
            $query->whereHas('entities', fn ($q) => $q->where('slug', $filters['entity']));
        }

        if (!empty($filters['event_type'])) {
            $query->whereHas('eventType', fn ($q) => $q->where('name', $filters['event_type']));
        }

        // no cover charge, for /calendar/free
        if (!empty($filters['free'])) {
            $query->where($table.'.door_price', 0);
        }

        // open to someone this age, for /calendar/min-age/{age}
        if (isset($filters['min_age']) && is_numeric($filters['min_age'])) {
            $query->where($table.'.min_age', '<=', (int) $filters['min_age']);
        }
    }

    /**
     * The feed filter only events have: (signed in) the viewer's relation to
     * the event.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Event> $query
     * @param array<string, mixed> $filters
     */
    private function applyEventOnlyFeedFilters($query, array $filters): void
    {
        if (empty($filters['display_type']) || $filters['display_type'] === 'all' || !$this->user) {
            return;
        }

        $userId = $this->user->id;
        $attended = function ($q) use ($userId) {
            $q->select('event_responses.event_id')
                ->from('event_responses')
                ->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
                ->where('response_types.name', '=', 'Attending')
                ->where('event_responses.user_id', '=', $userId);
        };

        match ($filters['display_type']) {
            'attending' => $query->whereIn('events.id', $attended),
            'not_attending' => $query->whereNotIn('events.id', $attended),
            'created' => $query->where('events.created_by', '=', $userId),
            'not_created' => $query->where('events.created_by', '!=', $userId),
            default => null,
        };
    }

    /**
     * Series for the "attending" display type are the ones the viewer follows.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Series> $query
     * @param array<string, mixed> $filters
     */
    private function applySeriesOnlyFeedFilters($query, array $filters): void
    {
        if (($filters['display_type'] ?? null) === 'attending' && $this->user) {
            $query->whereIn('series.id', $this->user->getSeriesFollowing()->modelKeys());
        }
    }

    /**
     * A calendar page whose events come from the ranged feed with $filters
     * preset (the page's own scope, not shown in its filter form).
     *
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $data
     */
    private function feedPage(array $filters, array $data = []): View
    {
        $query = $filters ? '?'.http_build_query(['filters' => $filters]) : '';

        return view('events.event-calendar-tw', array_merge([
            'calendarFeedUrl' => url('/calendar').$query,
            'initialDate' => Carbon::now()->format('Y-m-d'),
        ], $data));
    }

    /**
     * Display a listing of events related to entity.
     */
    public function calendarRelatedTo(Request $request, string $slug): View
    {
        $related = Entity::where('slug', '=', $slug)->firstOrFail();

        return $this->feedPage(['entity' => $related->slug], compact('related'));
    }

    /**
     * Display a listing of events by tag.
     */
    public function calendarTags(string $slug): View
    {
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        return $this->feedPage(['tag' => $tag->slug], compact('tag'));
    }

    /**
     * Display a calendar view of events.
     **/
    public function index(Request $request): View|JsonResponse
    {
        // FullCalendar asks this same URL (with the page's filters) for each visible
        // range; the page itself no longer inlines every event ever (#2167)
        if (!$request->has('start')) {
            return $this->indexPage($request);
        }

        [$rangeStart, $rangeEnd] = CalendarRange::fromRequest($request);
        $filters = (array) $request->get('filters', []);

        $eventsQuery = Event::query()->where(function ($query) {
            /* @phpstan-ignore-next-line */
            $query->visible($this->user);
        });
        $this->applyFeedFilters($eventsQuery, $filters, 'events');
        $this->applyEventOnlyFeedFilters($eventsQuery, $filters);

        // events in the requested range, with filters applied
        $events = $eventsQuery->whereBetween('events.start_at', [$rangeStart, $rangeEnd])
            ->with('eventType', 'visibility')
            ->get();

        // eager-load upcomingEvent so Series::nextEvent() uses the loaded relation
        // instead of issuing a query per series (Sentry N+1 on /calendar)
        $seriesQuery = Series::active()->with('visibility', 'occurrenceType', 'upcomingEvent');
        $this->applyFeedFilters($seriesQuery, $filters, 'series');
        $this->applySeriesOnlyFeedFilters($seriesQuery, $filters);

        return response()->json(array_merge(
            $events->map(fn (Event $event) => $this->eventItem($event))->all(),
            // the next scheduled instance of each series that falls in the range
            $this->seriesItems($this->shownSeries($seriesQuery->get()), $rangeStart, $rangeEnd)
        ));
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

        return $this->feedPage([], ['initialDate' => $year.'-'.$month.'-01']);
    }

    /**
     * Display a calendar view of events but only display the related tags.
     **/
    public function calendarTagOnly(): View
    {
        // the tag-only calendar fills itself in the browser
        $eventList = json_encode([]);

        return view('events.dynamic-tag-event-calendar-tw', compact('eventList'));
    }

    /**
     * Display a calendar view of events you are attending.
     *
     * @return view
     **/
    public function calendarAttending()
    {
        return $this->feedPage(['display_type' => 'attending'], ['slug' => 'Attending']);
    }

    /**
     * Display a calendar view of free events.
     *
     * @return view
     **/
    public function calendarFree()
    {
        return $this->feedPage(['free' => 1], ['slug' => 'No Cover']);
    }

    /**
     * Display a calendar view of all ages.
     *
     * @return view
     */
    public function calendarMinAge(int $age)
    {
        return $this->feedPage(['min_age' => $age], ['slug' => 'Min Age '.$age]);
    }

    /**
     * Display a listing of events by event type.
     */
    public function calendarEventTypes(string $type): View
    {
        $slug = Str::title(str_replace('-', ' ', $type));

        return $this->feedPage(['event_type' => $slug], compact('slug'));
    }
 
}
