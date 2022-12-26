<?php

namespace App\Http\Controllers;

use App\Events\EventCreated;
use App\Events\EventUpdated;
use App\Filters\EventFilters;
use App\Http\Requests\EventRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Mail\FollowingUpdate;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventReview;
use App\Models\EventType;
use App\Models\Follow;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Photo;
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
use App\Services\StringHelper;
use Carbon\Carbon;
use Exception;
use FacebookAds\Api as Api;
use FacebookAds\Object\Event as ObjectEvent;
use FacebookAds\Object\Fields\EventFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Str;
use Illuminate\View\View;
use Image;
use Storage;
use Illuminate\Http\UploadedFile;

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
        $this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update', 'indexAttending', 'calendarAttending']]);
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

        // inject Facebook into class
        Api::init(
            config('app.fb_app_id'),
            config('app.fb_app_secret'),
            config('app.fb_graph_version')
        );

        $this->facebook = Api::instance();

        $this->hasFilter = false;
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
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Event::query()
            ->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')
            ->select('events.*')
            // ->withCount('eventResponses')
        ;

        // set the default filter to starting today, can override
        $defaultFilter = ['start_at' => ['start' => Carbon::now()->format('Y-m-d')]];

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultFilters($defaultFilter)
            ->setDefaultSort(['events.start_at' => 'asc'])
        ;

        // get the result set from the builder
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

        return view('events.index')
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
    ): string {
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
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                    'slug' => $slug,
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('events'))
            ->render();
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
            ->setQueryBuilder(Event::query())
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

        return view('events.index')
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
     * Display a grid listing of the resource.
     *
     * @throws \Throwable
     */
    public function indexGrid(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_grid');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'indexGrid']));

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

        $query
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

        // get the events
        $events = $query
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.grid')
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
     * Display a grid of photos from events.
     *
     * @throws \Throwable
     */
    public function indexPhoto(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_photo');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'indexPhoto']));

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

        $query
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

        // get the events
        $events = $query
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.indexPhoto')
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
     * Display a listing of only future events.
     *
     * @return Response|View
     */
    public function indexFuture(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_future');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // get the base query for today's events and add any necessary joins for sorting
        $baseQuery = Event::future()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $future_events = $query
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.index')
            ->with(array_merge(
                [
                    'slug' => 'Future',
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('future_events'));
    }

    /*
     * Same as the 4-day window except uses the passed in date for the start date
     * @phpstan-param view-string $view
    */
    public function indexUpcoming(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $date = ''
    ): string {
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_upcoming');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // use the window to get the last date and set the criteria between
        $next_day = Carbon::parse($date)->addDays(1);
        $next_day_window = Carbon::parse($date)->addDays($this->defaultWindow);
        $prev_day = Carbon::parse($date)->subDays(1);
        $prev_day_window = Carbon::parse($date)->subDays($this->defaultWindow);

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Event::query()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'desc']);

        // NOTE normally a query would be created, a list entity result builder configured and events retrieved, but this uses ajax calls

        // handle the request if ajax
        if ($request->ajax()) {
            return view('events.4daysAjax')
                    ->with([
                        'date' => $date,
                        'window' => $this->defaultWindow,
                        'next_day' => $next_day,
                        'next_day_window' => $next_day_window,
                        'prev_day' => $prev_day,
                        'prev_day_window' => $prev_day_window,
                    ])
                    ->render();
        }

        return view('events.upcoming')
        ->with([
            'date' => $date,
            'window' => $this->defaultWindow,
            'next_day' => $next_day,
            'next_day_window' => $next_day_window,
            'prev_day' => $prev_day,
            'prev_day_window' => $prev_day_window,
        ]);
    }

    /*
     * Returns a list of four additional events to append to page
     * @phpstan-param view-string $view
    */
    public function indexAdd(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $date = ''
    ): string {
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_add');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // use the window to get the last date and set the criteria between
        $next_day = Carbon::parse($date)->addDays(1);
        $next_day_window = Carbon::parse($date)->addDays($this->defaultWindow);
        $prev_day = Carbon::parse($date)->subDays(1);
        $prev_day_window = Carbon::parse($date)->subDays($this->defaultWindow);

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Event::query()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'desc']);

        // NOTE normally a query would be created, a list entity result builder configured and events retrieved, but this uses ajax calls

        // handle the request if ajax
        if ($request->ajax()) {
            return view('events.addDays')
                    ->with([
                        'date' => $date,
                        'window' => $this->defaultWindow,
                        'next_day' => $next_day,
                        'next_day_window' => $next_day_window,
                        'prev_day' => $prev_day,
                        'prev_day_window' => $prev_day_window,
                    ])
                    ->render();
        }

        return view('events.upcoming')
        ->with([
            'date' => $date,
            'window' => $this->defaultWindow,
            'next_day' => $next_day,
            'next_day_window' => $next_day_window,
            'prev_day' => $prev_day,
            'prev_day_window' => $prev_day_window,
        ]);
    }

    /**
     * Display a listing of today's events.
     *
     * @return Response|View
     */
    public function indexToday(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_today');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // get the base query for today's events and add any necessary joins for sorting
        $baseQuery = Event::today()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        $query
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

        // get the events
        $events = $query
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.index')
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
            ->with(compact('events'));
    }

    /**
     * Display a listing of only past events.
     *
     * @return Response|View
     */
    public function indexPast(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_past');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // get the base query for today's events and add any necessary joins for sorting
        $baseQuery = Event::past()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $past_events = $query
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.index')
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
            ->with(compact('past_events'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|View
     */
    public function indexAttending(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        $this->middleware('auth');

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_attending');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->user->getAttending()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        // set the default filter to starting today, can override
        $defaultFilter = ['start_at' => ['start' => Carbon::now()->format('Y-m-d')]];

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultFilters($defaultFilter)
            ->setDefaultSort(['events.start_at' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $events = $query
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.index')
        ->with(array_merge(
            [
                'slug' => 'Attending',
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters(),
                'filterRoute' => 'events.attending',
                'key' => 'internal_event_attending',
                'redirect' => 'events.attending',
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
            ->with(compact('events'));
    }

    /**
     * Display a simple text feed of future events.
     *
     * @return View
     */
    public function feed(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
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
        // @phpstan-ignore-next-line
        $events = $query->visible($this->user)
            ->with('visibility', 'venue')
            ->paginate(1000);

        return view('events.feed', compact('events'));
    }

    /**
     * Display a simple text feed of future events by tag.
     */
    public function feedTags(string $tag): View
    {
        // set number of results per page
        $events = Event::getByTag(ucfirst($tag))->future()->simplePaginate(10000);

        return view('events.feed', compact('events'));
    }

    /**
     * Display very short text list
     *
     * @return View
     */
    public function briefText(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
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
        // @phpstan-ignore-next-line
        $events = $query->visible($this->user)
            ->with('visibility', 'venue')
            ->paginate(1000);

        return view('events.briefText', compact('events'));
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
     * TODO This is not used in the UI - find where to add
     * Send a reminder to all users who are attending this event.
     *
     * @return RedirectResponse
     */
    public function remind(int $id, Mail $mail)
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
     * Send a reminder to all users about all events they are attending.
     *
     * @return Response|RedirectResponse
     */
    public function daily()
    {
        // get all the users
        $users = User::orderBy('name', 'ASC')->get();

        $site = config('app.app_name');
        $url = config('app.url');

        // cycle through all the users
        foreach ($users as $user) {
            $interests = [];
            $seriesList = [];

            $events = $user->getAttendingFuture()->take(100);

            // build an array of future events based on tags the user follows
            $tags = $user->getTagsFollowing();
            if (count($tags) > 0) {
                foreach ($tags as $tag) {
                    if (count($tag->todaysEvents()) > 0) {
                        $interests[$tag->name] = $tag->todaysEvents();
                    }
                }
            }

            // build an array of series that the user is following
            $series = $user->getSeriesFollowing();
            if (count($series) > 0) {
                foreach ($series as $s) {
                    // if the series does not have NO SCHEDULE AND CANCELLED AT IS NULL
                    if ($s->occurrenceType->name !== 'No Schedule' && (null === $s->cancelled_at)) {
                        // add matches to list
                        $next_date = $s->nextOccurrenceDate()->format('Y-m-d');

                        // today's date is the next series date
                        if ($next_date === Carbon::now()->format('Y-m-d')) {
                            $seriesList[] = $s;
                        }
                    }
                }
            }

            Mail::send('emails.daily-events', ['user' => $user, 'events' => $events, 'seriesList' => $seriesList, 'interests' => $interests, 'url' => $url, 'site' => $site], function ($email) use ($user) {
                $email->from('admin@events.cutupsmethod.com', 'Event Repo');
                $email->to($user->email, $user->name)->subject('Event Repo: Daily Events Reminder');
            });
        }

        flash()->success('Success', 'You sent an email reminder to '.count($users).' users about events they are attending');

        return back();
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

        return view('events.event-calendar', compact('eventList', 'related', 'initialDate'));
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

        return view('events.event-calendar', compact('eventList', 'tag', 'initialDate'));
    }

    /**
     * Display a calendar view of events.
     **/
    public function calendar(): View
    {
        // set the initial date of the calendar that is displayed
        $initialDate = Carbon::now()->format('Y-m-d');

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

        return view('events.event-calendar', compact('eventList', 'initialDate'));
    }

    /**
     * Display a calendar view of events by date
     **/
    public function calendarByDate(
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

        return view('events.event-calendar', compact('eventList', 'initialDate'));
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
        //         'url' => '/events/'.$event->id,
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
        //             'url' => '/series/'.$s->id,
        //             'backgroundColor' => '#99bcdb',
        //             'description' => $s->short,
        //         ];
        //     }
        // }

        $eventList = json_encode($eventList);

        return view('events.dynamic-tag-event-calendar', compact('eventList'));
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
        return view('events.event-calendar');
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
        $series = $this->user->getSeriesFollowing();

        // filter for only events that are public or that were created by the current user and are not "no schedule"
        $series = $series->filter(function ($e) {
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
                'url' => '/events/'.$event->id,
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
                    'url' => '/series/'.$s->id,
                    'backgroundColor' => '#99bcdb',
                    'description' => $s->short,
                ];
            }
        }

        $eventList = json_encode($eventList);

        return view('events.event-calendar', compact('eventList', 'slug', 'initialDate'));
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
            ->get();

        // filter public events and those created by the current user
        $events = $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::where('door_price', 0)->active()->get();

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

        $eventList = json_encode($eventList);

        return view('events.event-calendar', compact('eventList', 'slug','initialDate'));
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
            ->get();

        // filter only public events and those created by the user
        $events = $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::where('min_age', '<=', $age)->active()->get();

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

        $eventList = json_encode($eventList);

        return view('events.event-calendar', compact('eventList', 'slug','initialDate'));
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
            ->get();

        $events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // get all the upcoming series events
        $series = Series::getByType($slug)->active()->get();

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

        $eventList = json_encode($eventList);

        return view('events.event-calendar', compact('eventList', 'slug','initialDate'));
    }

    /**
     * Show a form to create a new event.
     **/
    public function create(): View
    {
        return view('events.create')->with($this->getFormOptions());
    }

    /**
     * Makes a call to the FB API if there is a link present and downloads the event cover photo.
     *
     * @param int $id
     */
    public function importPhoto($id, ImageHandler $imageHandler): RedirectResponse
    {
        $event = Event::findOrFail($id);

        if (empty($event->primary_link)) {
            flash()->error('Error', 'You must have a valid Facebook event linked to import the photo.  To add from your desktop, drop an image file to the right.');

            return back();
        }

        if ($this->addFbPhoto($event, $imageHandler)) {
            flash()->success('Success', 'Successfully imported the event cover photo.');
        }

        return back();
    }

    /**
     * Call to Facebook to login the user and ask them to grant the app permission.
     */
    private function getFacebookLoginUrl(string $permissions): string
    {
        $fbGraphVersion = config('app.fb_graph_version');
        $fbAppId = config('app.fb_app_id');
        $endpoint = 'https://www.facebook.com/'.$fbGraphVersion.'/dialog/oauth';

        $params = [
            'client_id' => $fbAppId,
            'redirect_uri' => '/',
            'scope' => $permissions,
            'auth_type' => 'rerequest',
        ];

        return $endpoint.'?'.http_build_query($params);
    }

    /**
     * Call to Facebook after login to get an access token.
     */
    private function getAccessTokenWithCode(string $code): array
    {
        $endpoint = 'https://graph.facebook.com/'.config('app.fb_graph_version').'/oauth/access_token';

        $params = [
            'client_id' => config('app.fb_app_id'),
            'client_secret' => config('app.fb_app_secret'),
            'redirect_url' => '/',
            'code' => $code,
        ];

        return $this->makeApiCall($endpoint, 'GET', $params);
    }

    /**
     * Curl API call.
     */
    private function makeApiCall(string $endpoint, string $type, array $params): array
    {
        $ch = curl_init();

        // create endpoint with params
        $apiEndpoint = $endpoint.'?'.http_build_query($params);

        // set other curl options
        curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // get response
        $response = curl_exec($ch);

        curl_close($ch);

        return [
            'type' => $type,
            'endpoint' => $endpoint,
            'params' => $params,
            'api_endpoint' => $apiEndpoint,
            'data' => json_decode($response, true),
        ];
    }

    /**
     * This is the callback from the login that will get and set an access token.
     **/
    public function fbAuthToken(): mixed
    {
        // build an ajax call to get an access token?

        // check if there is an access token
        if (isset($_COOKIE['fb-token'])) {
            return $_COOKIE['fb-token'];
        }

        // if not, seet if we made the login
        if (isset($_GET['code'])) {
            $accessToken = $this->getAccessTokenWithCode($_GET['code']);
            setcookie('fb-token', $accessToken['data'], time() + (86400 * 30), '/');
            dump($accessToken);
        }

        return null;
    }

    /**
     * Add a FB photo to an event.
     */
    protected function addFbPhoto(Event $event, ImageHandler $imageHandler): bool | RedirectResponse
    {
        // get the FB event id from the primary link
        $str = $event->primary_link;
        $spl = explode('/', $str);

        if (!isset($spl[4])) {
            flash()->error('Error', 'The link supplied does not have an importable photo.  Using a facebook event link is recommended.');

            return back();
        }

        $event_id = $spl[4];

        try {
            // FB API new call - OLDER API
            // $token = $this->facebook->getJavaScriptHelper()->getAccessToken();
            // $response = $this->facebook->get($event_id.'?fields='.$fields, $token);

            // FB facebook-php-business-sdk - Get an auth token by logging in if there is no current token set
            // make an ajax call using the facebook url

            // check cookies for the token
            $fbToken = null;
            if (isset($_COOKIE['fb-token'])) {
                $fbToken = $_COOKIE['fb-token'];
            }

            Api::init(
                config('app.fb_app_id'),
                config('app.fb_app_secret'),
                $fbToken
            );

            // not sure why this needs to be set, but the docs show this
            $facebook = Api::instance();

            $fbEventFields = [EventFields::COVER];

            $fbEvent = (new ObjectEvent($event_id))->getSelf($fbEventFields);

            if ($cover = $fbEvent->cover) {
                $source = $cover['source'];
                
                // temporarily store the file
                $content = file_get_contents($source);
                $fileName = time().'_temp.jpg';
                file_put_contents(storage_path().'/app/public/photos/'.$fileName, $content);
                $file = new UploadedFile(storage_path().'/app/public/photos/'.$fileName, 'temp.jpg', null, null, false);

                // store the file externally
                $stored = Storage::disk('external')->putFileAs('photos', $file, $fileName, 'public');

                // make the photo object from the file in the request
                $photo = $imageHandler->makePhoto($file);
                if ($photo !== null) {
                    // count existing photos, and if zero, make this primary
                    if (0 === count($event->photos)) {
                        $photo->is_primary = 1;
                    }

                    $photo->save();

                    // attach to event
                    $event->addPhoto($photo);
                }
                
                // remove the original file
                unlink($file->getPathname());
            }
        } catch (Exception $e) {
            flash()->error('Error', 'You could not import the image.  Error: '.$e->getMessage());

            return false;
        }

        return true;
    }


    /**
     * Makes a call to the FB API if there is a link present and downloads the event cover photo.
     */
    public function importPhotos(ImageHandler $imageHandler): RedirectResponse
    {
        // get all the events with no photo, but a fb url
        $events = Event::has('photos', '<', 1)
            ->where('primary_link', '<>', '')
            ->where('primary_link', 'like', '%facebook%')
            ->get();

        // FB facebook-php-business-sdk
        $fbEventFields = [
            EventFields::ATTENDING_COUNT,
            EventFields::CATEGORY,
            EventFields::COVER,
            EventFields::INTERESTED_COUNT,
            EventFields::NAME,
            EventFields::NOREPLY_COUNT,
            EventFields::MAYBE_COUNT,
        ];

        foreach ($events as $event) {
            $str = $event->primary_link;
            $spl = explode('/', $str);
            $event_id = $spl[4];

            $fbEvent = (new ObjectEvent($event_id))->getSelf($fbEventFields);

            // get the cover from FB
            if (($cover = $fbEvent->cover) && ($source = $cover->getField('source'))) {
                // temporarily store the file
                $content = file_get_contents($source);
                $fileName = time().'_temp.jpg';
                file_put_contents(storage_path().'/app/public/photos/'.$fileName, $content);
                // gets the uploaded file from the local path
                $file = new UploadedFile(storage_path().'/app/public/photos/'.$fileName, 'temp.jpg', null, null, false);

                // store the file externally using the original name
                $stored = Storage::disk('external')->putFileAs('photos', $file, $fileName, 'public');

                // make the photo object from the file in the request
                /** @var Photo $photo */
                $photo = $imageHandler->makePhoto($file);
                if ($photo !== null) {
                    // count existing photos, and if zero, make this primary
                    if (0 === count($event->photos)) {
                        $photo->is_primary = 1;
                    }

                    $photo->save();

                    // attach to event
                    /* @var Event $event */
                    $event->addPhoto($photo);
                }

                // remove the original file
                unlink($file->getPathname());
            }
        }

        flash()->success('Success', 'Successfully imported the event cover photos.');

        return back();
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

    public function show(?Event $event, EmbedExtractor $embedExtractor): string
    {
        if (!$event) {
            abort(404);
        }

        $thread = Thread::where('event_id', '=', $event->id)->first();

        // check blacklist status
        $blacklist = $this->checkBlackList($event);

        // extract all the links from the event body and convert into embeds
        $embeds = $embedExtractor->getEmbedsForEvent($event);

        return view('events.show', compact('event', 'embeds'))->with(['thread' => $thread, 'blacklist' => $blacklist])->render();
    }

    public function store(EventRequest $request, Event $event, ImageHandler $imageHandler): RedirectResponse
    {
        $msg = '';

        $input = $request->all();

        // transform the slug passed in the request
        $input['slug'] = Str::slug($request->input('slug', '-'));

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

        // TODO figure out why this is failing after Laravel 9 upgrade - doesnt seem to be doing anything, so no big deal..
        // dispatch notifications that the event was created
        EventCreated::dispatch($event);

        flash()->success('Success', 'Your event has been created');

        // check if a FB link was included
        if (false !== strpos($event->primary_link, 'facebook')) {
            // try to import the photo
            $this->addFbPhoto($event, $imageHandler);
        }

        $photo = $event->getPrimaryPhoto();

        // make a call to notify all users who are following any of the tags/keywords if the event starts in the future
        if ($event->start_at >= Carbon::now()) {
            // only do the notification if there is a photo
            if ($photo !== null) {
                $this->notifyFollowing($event);
            }
        }

        // add a twitter notification if the user is admin
        if ($this->user->hasGroup('super_admin') && config('app.twitter_consumer_key') !== '999') {
            // only tweet if there is a primary photo
            if ($photo !== null) {
                $event->notify(new EventPublished());
            }
        }

        return redirect()->route('events.show', compact('event'));
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
                if (!array_key_exists($user->user_id, $users)) {
                    Mail::to($user->email)
                        ->send(new FollowingUpdate($url, $site, $admin_email, $reply_email, $user, $event, $entity));
                    $users[$user->user_id] = $entity->name;
                } else {
                    $users[$user->user_id] = $users[$user->user_id].', '.$entity->name;
                }
            }
        }
    }

    public function edit(Event $event): View
    {
        $this->middleware('auth');

        return view('events.edit', compact('event'))->with($this->getFormOptions());
    }

    public function update(Event $event, EventRequest $request): RedirectResponse
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

        flash()->success('Success', 'Your event has been updated');

        return redirect()->route('events.show', compact('event'));
    }

    protected function unauthorized(EventRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Event $event): RedirectResponse
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

        // unlink the temp file
        if ($photo = $event->getPrimaryPhoto()) {
            unlink(storage_path().'/app/public/photos/temp/'.$photo->name);
        };

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
     */
    public function review(int $id, Request $request): RedirectResponse
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
        $review->expectation = $request->input('expectation', null);
        $review->rating = $request->input('rating', null);
        $review->review = $request->input('review', null);
        $review->save();

        flash()->success('Success', 'You reviewed the event - '.$event->name);

        return back();
    }

    /**
     * Display a listing of events by tag.
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug,
        StringHelper $stringHelper
    ): View {
        // get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_tags');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder(Event::query())
            ->setDefaultSort(['events.start_at' => 'desc'])
            ->setParentFilter(['tag' => $slug]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $pastQuery = $listResultSet->getList();
        $futureQuery = clone $pastQuery;

        // @phpstan-ignore-next-line
        $future_events = $futureQuery
            ->future()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->paginate($listResultSet->getLimit());

        $future_events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // @phpstan-ignore-next-line
        $past_events = $pastQuery
        // @phpstan-ignore-next-line
            ->past()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

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
            ->with(compact('past_events'))
            ->with(compact('tag'));
    }

    /**
     * Display a listing of events related to entity.
     *
     * @return View
     */
    public function indexRelatedTo(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug
    ) {
        // get the entity by the slug name
        $related = Entity::where('slug', '=', $slug)->firstOrFail();

        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_related');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder(Event::query())
            ->setDefaultSort(['events.start_at' => 'desc'])
            ->setParentFilter(['related' => $related->name]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $pastQuery = $listResultSet->getList();
        $futureQuery = clone $pastQuery;

        // @phpstan-ignore-next-line
        $future_events = $futureQuery
            ->future()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->paginate($listResultSet->getLimit());

        $future_events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // @phpstan-ignore-next-line
        $past_events = $pastQuery
            ->past()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

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
            ->with(compact('past_events'))
            ->with(compact('related'));
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
            ->setDefaultSort(['evebts.start_at' => 'desc']);

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
     * Display a listing of events by venue.
     *
     * @return View
     */
    public function indexVenues(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug
    ) {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_index');

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

        $future_events = Event::getByVenue(strtolower($slug))
            ->future()
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->paginate($this->limit);

        $past_events = Event::getByVenue(strtolower($slug))
            ->past()
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->paginate($this->limit);

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
            ->with(compact('past_events'))
            ->with(compact('slug'));
    }

    /**
     * Display a listing of events by type.
     *
     * @return View
     */
    public function indexTypes(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $type
    ) {
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_types');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder(Event::query())
            ->setDefaultSort(['events.start_at' => 'desc'])
            ->setParentFilter(['event_type' => $type]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $pastQuery = $listResultSet->getList();
        $futureQuery = clone $pastQuery;

        // @phpstan-ignore-next-line
        $future_events = $futureQuery
            ->future()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->paginate($listResultSet->getLimit());

        $future_events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // @phpstan-ignore-next-line
        $past_events = $pastQuery
            ->past()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

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
            ->with(compact('past_events'))
            ->with(compact('type'));
    }

    /**
     * Display a listing of events by series.
     *
     * @return View
     */
    public function indexSeries(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug
    ) {
        $slug = Str::title(str_replace('-', ' ', $slug));

        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_series');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder(Event::query())
            ->setDefaultSort(['events.start_at' => 'desc'])
            ->setParentFilter(['series' => $slug]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $pastQuery = $listResultSet->getList();
        $futureQuery = clone $pastQuery;

        // @phpstan-ignore-next-line
        $future_events = $futureQuery
            ->future()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->paginate($listResultSet->getLimit());

        $future_events->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // @phpstan-ignore-next-line
        $past_events = $pastQuery
            ->past()
            ->orderBy('events.start_at', 'ASC')
            ->orderBy('events.name', 'ASC')
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        $past_events->filter(function ($e) {
            return ($e->visibility && 'Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

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
        // no filters or sorting applied, just future events
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
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): void
    {
        // confirm the file is one of these types
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        // get the event
        if ($event = Event::find($id)) {

            // make the photo object from the file in the request, returning photo object
            $photo = $imageHandler->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if (isset($event->photos) && 0 === count($event->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to event
            $event->addPhoto($photo);

            // make a call to notify all users who are following any of the tags/keywords if the event starts in the future
            if ($event->start_at >= Carbon::now()) {
                // only do the notification if there is exactly one photo
                if (1 === count($event->photos)) {
                    $this->notifyFollowing($event);
                }
            }
        }
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
            'body' => $event->short ? $event->short : $event->name,
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

    public function exportAttending(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        RssFeed $feed
    ): View {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_attending');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->user->getAttending()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        $defaultFilter = ['start_at' => ['start' => Carbon::now()->format('Y-m-d')]];

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultFilters($defaultFilter)
            ->setDefaultSort(['events.start_at' => 'asc']);

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

    /**
     * Display events that the specified user is attending
     *
     * @return Response|View
     */
    public function indexUserAttending(
        int $id,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        // find user or fail
        $user = User::findOrFail($id);

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_attending');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $user->getAttending()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*');

        // set the default filter to starting today, can override
        $defaultFilter = ['start_at' => ['start' => Carbon::now()->format('Y-m-d')]];

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultFilters($defaultFilter)
            ->setDefaultSort(['events.start_at' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $events = $query
            ->with('visibility', 'venue')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('events.indexUserAttending')
        ->with(array_merge(
            [
                'slug' => 'Attending',
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters(),
                'filterRoute' => 'events.attending',
                'key' => 'internal_event_attending',
                'redirect' => 'events.attending',
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
            ->with(compact('events', 'user'));
    }

    
    /**
     * Reset the filtering of events the user is attending.
     *
     * @return RedirectResponse|View
     */
    public function resetUserAttending(
        int $id,
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ) {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_event_attending';
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('users.attending', ['id' => $id]);
    }


    /**
     * Reset the limit, sort, order.
     *
     * @throws \Throwable
     */
    public function rppResetUserAttending(
        int $id,
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_event_attending';
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('users.attending', ['id' => $id]);
    }

}
