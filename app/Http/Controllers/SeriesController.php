<?php

namespace App\Http\Controllers;

use App\Filters\SeriesFilters;
use App\Http\Controllers\Concerns\BuildsListPages;
use App\Http\Controllers\Concerns\LoadsEmbeds;
use App\Http\Requests\SeriesRequest;
use App\Http\Response\ResultSet\ListResultSet;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Follow;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\SeriesWriter;
use App\Services\Embeds\OembedExtractor;
use App\Services\ImageHandler;
use App\Services\RssFeed;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\StringHelper;
use App\Services\TempImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Str;

class SeriesController extends Controller
{
    use BuildsListPages;
    use LoadsEmbeds;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected int $childLimit;

    protected array $defaultSortCriteria;

    protected array $filters;

    protected int $page;

    // this is the class specifying the filters methods for each field
    protected SeriesFilters $filter;

    public function __construct(SeriesFilters $filter)
    {
        // auth first, so a guest is sent to sign in and back (#2108) rather than to the verify notice
        $this->middleware(['auth', 'verified'], ['only' => ['create', 'createOccurrence', 'edit', 'store', 'update']]);
        $this->middleware(['auth', 'verified'], ['only' => ['destroy']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.series.';

        // default list variables

        // default list variables
        $this->defaultSort = 'series.created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultLimit = 5;

        // set list variables
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->childLimit = 10;
        $this->page = 1;
        $this->defaultSortCriteria = ['series.created_at' => 'desc'];
        parent::__construct();
    }

    /**
     * The series list as the filter form submits it. Unlike index() it has no
     * default visibility filter.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startSeriesList($listParamSessionStore, $listEntityResultBuilder, 'internal_series_index', $this->baseQuery());

        return $this->seriesListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * Start a series list page (#2406): every series list shares one session
     * base index, the series filters and the default sort.
     *
     * @param array<string, mixed> $defaultFilters
     * @param array<string, mixed> $parentFilter
     */
    private function startSeriesList(
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $keyPrefix,
        Builder $baseQuery,
        array $defaultFilters = [],
        array $parentFilter = [],
    ): ListResultSet {
        return $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_series',
            $keyPrefix,
            action([SeriesController::class, 'index']),
            $this->filter,
            $baseQuery,
            $this->defaultSortCriteria,
            $defaultFilters,
            $parentFilter,
        );
    }

    /**
     * Render the series list view for a started list.
     *
     * @param array<string, mixed> $extra more view data (the page's slug, tag, ...)
     *
     * @throws \Throwable
     */
    private function seriesListPage(
        ListParameterSessionStore $listParamSessionStore,
        ListResultSet $listResultSet,
        array $extra = [],
    ): string {
        $series = $listResultSet->getList()
            // everything series/card-tw reads per card (#2173)
            ->with(Series::CARD_EAGER_LOAD)
            ->paginate($listResultSet->getLimit());

        return view('series.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet, extra: $extra))
            ->with(compact('series'))
            ->render();
    }

    /**
     * Get the base criteria.
     */
    protected function baseQuery(): Builder
    {
        return Series::query()
            ->leftJoin('event_types', 'series.event_type_id', '=', 'event_types.id')
            ->leftJoin('visibilities', 'series.visibility_id', '=', 'visibilities.id')
            ->leftJoin('occurrence_types', 'series.occurrence_type_id', '=', 'occurrence_types.id')
            ->orderBy('occurrence_type_id', 'ASC')
            ->orderBy('occurrence_week_id', 'ASC')
            ->orderBy('occurrence_day_id', 'ASC')
            // only series the viewer may see; the default "public" filter can be overridden
            ->visible($this->user)
            ->select('series.*');
    }

    /**
     * Reset the rpp, sort, order.
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_series', $request->get('key') ?? 'internal_series_index', filters: false);

        return redirect()->route('series.index');
    }

    /**
     * Reset the filtering of series.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_series', $request->get('key') ?? 'internal_series_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'series.index'));
    }

    /**
     * Apply filters from URL query parameters and redirect to filtered view.
     * This allows users to share filtered series lists via URL.
     *
     * @return RedirectResponse
     */
    public function applyFilterFromUrl(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // Initialize session store
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_index');

        // Set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        // Get filters from query parameters
        $filters = $request->input('filters', []);

        if (!empty($filters)) {
            // Set filters in session
            $listParamSessionStore->setFilters($filters);

            // Set sorting if provided
            if ($request->has('sort')) {
                $listParamSessionStore->setSortFieldName($request->input('sort'));
            }
            if ($request->has('direction')) {
                $listParamSessionStore->setSortDirection($request->input('direction'));
            }
            if ($request->has('limit')) {
                $listParamSessionStore->setLimit((int) $request->input('limit'));
            }

            // Save to session
            $listParamSessionStore->save();
        }

        // Redirect to series filter page to apply the filters
        return redirect()->route('series.filter');
    }

    /**
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startSeriesList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_series_index',
            $this->baseQuery(),
            defaultFilters: ['visibility' => Visibility::VISIBILITY_PUBLIC],
        );

        return $this->seriesListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * @throws \Throwable
     */
    public function indexFollowing(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $baseQuery = Series::join('follows', 'series.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'series')
            ->where('follows.user_id', '=', $this->user->id)
            // a followed series may since have been made private
            ->visible($this->user)
            ->orderBy('follows.created_at', 'desc')
            ->select('series.*');

        $listResultSet = $this->startSeriesList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_series_index',
            $baseQuery,
            defaultFilters: ['visibility' => Visibility::VISIBILITY_PUBLIC],
        );

        return $this->seriesListPage($listParamSessionStore, $listResultSet, ['type' => 'Following']);
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['series.name' => 'Name', 'series.created_at' => 'Created At', 'event_types.name' => 'Event Type'],
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
            'occurrenceTypeOptions' => ['' => ''] + OccurrenceType::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'occurrenceWeekOptions' => ['' => ''] + OccurrenceWeek::orderBy('id', 'ASC')->pluck('name', 'name')->all(),
            'occurrenceDayOptions' => ['' => ''] + OccurrenceDay::orderBy('id', 'ASC')->pluck('name', 'name')->all(),
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * @throws \Throwable
     */
    public function indexCancelled(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startSeriesList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_series_cancelled',
            $this->baseQuery()->whereNotNull('cancelled_at'),
        );

        return $this->seriesListPage($listParamSessionStore, $listResultSet, ['slug' => 'Cancelled']);
    }

    /**
     * Display a listing of event series in a week view.
     *
     * @throws \Throwable
     */
    public function indexWeek(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startSeriesList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            // shares its filters and sort with the cancelled list, as it always has
            'internal_series_cancelled',
            // @phpstan-ignore-next-line
            $this->baseQuery()->future(),
        );

        return $this->seriesListPage($listParamSessionStore, $listResultSet, ['slug' => 'Week']);
    }

    /**
     * Display a listing of series related to entity.
     *
     * @throws \Throwable
     */
    public function indexRelatedTo(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug
    ): string {
        // get the entity by the slug name
        $related = Entity::where('slug', '=', $slug)->firstOrFail();

        $listResultSet = $this->startSeriesList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_series_related',
            $this->baseQuery(),
            parentFilter: ['related' => $slug],
        );

        return $this->seriesListPage($listParamSessionStore, $listResultSet, compact('related'));
    }

    /**
     * Display a listing of series by tag.
     *
     * @throws \Throwable
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug,
        StringHelper $stringHelper
    ): string {
        // get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $listResultSet = $this->startSeriesList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_series_tags',
            $this->baseQuery(),
            parentFilter: ['tag' => $slug],
        );

        return $this->seriesListPage($listParamSessionStore, $listResultSet, compact('tag'));
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
            'entityOptions' => Entity::active()->orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'occurrenceTypeOptions' => ['' => ''] + OccurrenceType::pluck('name', 'id')->all(),
            'dayOptions' => ['' => ''] + OccurrenceDay::pluck('name', 'id')->all(),
            'weekOptions' => ['' => ''] + OccurrenceWeek::pluck('name', 'id')->all(),
            'userOptions' => User::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Show a form to create a new series.
     **/
    public function create(): View
    {
        // initialize a series and pass in
        $series = new Series();
        $series->visibility_id = Visibility::VISIBILITY_PUBLIC;

        return view('series.create', compact('series'))
            ->with($this->getSeriesFormOptions());
    }

    /**
     * Relations events/card-tw reads per card (venue, type, visibility, tags,
     * photos, entities, threads, and the viewer's response for the attend
     * button), so a grid of cards is a fixed number of queries, not N per event.
     *
     * @return array<int|string, mixed>
     */
    private function eventCardEagerLoad(): array
    {
        $eager = ['venue.photos', 'venue.locations', 'eventType', 'visibility', 'tags', 'photos', 'entities.roles', 'series.photos', 'threads', 'showings'];
        if ($this->user) {
            $eager['eventResponses'] = function ($query) {
                $query->where('user_id', $this->user->id)->with('responseType');
            };
        }

        return $eager;
    }

    /**
     * The lineup: entities on the upcoming events or, when there aren't any,
     * on the most recent edition's events (those in $editionYear), most
     * frequent first. Two queries at most beyond $upcomingEvents.
     *
     * @param \Illuminate\Support\Collection<int, Event> $upcomingEvents
     *
     * @return \Illuminate\Support\Collection<int, Entity>
     */
    private function lineupEntities(Series $series, $upcomingEvents, ?int $editionYear)
    {
        $lineupEventIds = $upcomingEvents->pluck('id');
        if ($lineupEventIds->isEmpty() && $editionYear) {
            $lineupEventIds = Event::where('series_id', $series->id)->visible($this->user)->whereYear('start_at', $editionYear)->pluck('id');
        }

        if ($lineupEventIds->isEmpty()) {
            return collect();
        }

        return Entity::select('entities.*')
            ->selectRaw('COUNT(entity_event.event_id) as frequency')
            ->join('entity_event', 'entities.id', '=', 'entity_event.entity_id')
            ->whereIn('entity_event.event_id', $lineupEventIds)
            ->with('roles')
            ->groupBy('entities.id')
            ->orderByDesc('frequency')
            ->limit(24)
            ->get();
    }

    public function show(Series $series, OembedExtractor $embedExtractor): View
    {
        // 404 rather than 403 so private and proposal series can't be probed
        abort_unless($series->isVisibleTo($this->user), 404);

        // eager-load relations consumed by the page + JSON-LD; upcomingEvent lets
        // Series::nextEvent() (used by getSeoTitleFormat()/getFestivalYear() below,
        // for both this request and the view's own title render) reuse the cached
        // relation instead of re-querying.
        $series->loadMissing(array_merge(['photos', 'venue.locations', 'upcomingEvent'], \App\Services\SeriesSchema::EAGER_LOAD));

        $eventEager = $this->eventCardEagerLoad();
        // Past Events & Archive grid: only events that have already started.
        // Uses the same now() boundary as $upcomingEvents below (not
        // Event::past()'s start-of-today) so no event falls between the two.
        // A short run is past once its last showing has started (#2375).
        $events = Event::where('series_id', $series->id)
            ->with($eventEager)
            ->visible($this->user)
            ->where('events.last_start_at', '<', now())
            ->orderBy('start_at', 'DESC')
            ->paginate($this->childLimit);

        // Upcoming events for the Schedule section: same eager loads as the
        // archive grid above, but ascending and unpaginated. Built from
        // Event::query() (rather than $series->events()) so ->visible()'s
        // local scope type-resolves against Builder<Event> for phpstan.
        $upcomingEvents = Event::where('series_id', $series->id)
            ->with($eventEager)
            ->visible($this->user)
            // a short run until its last showing, by its next one (#2375)
            ->occurringBetween(now())
            ->orderByNextStart(now())
            ->get();

        // Edition year (next upcoming event, else most recent past event) —
        // used to scope the "most recent edition" lineup fallback below for
        // ANY series. $festivalYear (passed to the view for the Schedule
        // heading) is the same value, but only for festivals: an ordinary
        // weekly/monthly series' recurring events aren't "editions" and
        // shouldn't get a fabricated year in their heading.
        $editionYear = $series->getFestivalYear();
        $festivalYear = $series->isFestival() ? $editionYear : null;

        $lineupEntities = $this->lineupEntities($series, $upcomingEvents, $editionYear);

        // posts/briefList renders each thread's posts with their user/tags/entities.
        $threads = $series->threads()
            ->visible($this->user)
            ->with(['posts.user', 'posts.tags', 'posts.entities'])
            ->paginate($this->childLimit);

        // Embeds are deferred to an AJAX call via the playlist-tw placeholder.
        $embeds = [];

        // "Add to player" (#2366) when the series queue would have someone in it:
        // the series' own entities, or the lineups of its upcoming events
        $playerAvailable = $series->entities->contains->isPlayable()
            || Entity::query()
                ->whereHas('events', fn ($q) => $q->whereIn('events.id', $upcomingEvents->pluck('id')))
                ->whereHas('links', fn ($q) => $q->where(fn ($w) => $w->where('url', 'like', '%soundcloud.com%')->orWhere('url', 'like', '%bandcamp.com%')))
                ->with(['links', 'roles'])
                ->get()
                ->contains->isPlayable();

        return view('series.show-tw', compact('series', 'events', 'threads', 'embeds', 'upcomingEvents', 'lineupEntities', 'festivalYear', 'playerAvailable'));
    }

    public function store(SeriesRequest $request, SeriesWriter $writer, TempImageStore $tempImages): RedirectResponse
    {
        // validated() leaves out the photo-attachment tokens and anything else
        // the form carries that isn't a series attribute
        $series = $writer->create(
            $this->user,
            $request->validated(),
            $request->input('tag_list', []),
            $request->input('entity_list', []),
            $request->eventLinkId ? (int) $request->eventLinkId : null,
        );

        // Attach the image the user chose on the create form, whether or not they
        // ran it through analysis. Done after the writer copied a linked event's photos, so that
        // the user's own upload wins is_primary over any copied event photos.
        $tempImages->attachFromRequest($request, $series);

        flash()->success('Success', 'Your event series has been created');

        return redirect()->route('series.show', compact('series'));
    }

    public function edit(Series $series): View|RedirectResponse
    {
        if ($this->user->cannot('update', $series)) {
            Session::flash('flash_message', ['title' => 'Access Denied', 'message' => 'You do not have permission to edit this series.', 'level' => 'error']);

            return redirect()->route('series.show', compact('series'));
        }

        return view('series.edit', compact('series'))
            ->with($this->getSeriesFormOptions());
    }

    public function export(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        RssFeed $feed
    ): View {
        $listResultSet = $this->startSeriesList($listParamSessionStore, $listEntityResultBuilder, 'internal_series_index', $this->baseQuery());

        $series = $listResultSet->getList()
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        return view('series.feed', compact('series'));
    }

    public function createOccurrence(Request $request): View
    {
        // create an event occurrence based on the template of a series the user can see
        $series = Series::find($request->id);
        abort_unless($series && $series->isVisibleTo($this->user), 404);

        $seriesOptions = ['' => ''] + Series::visible($this->user)->orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $userOptions = ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        // calculate the next occurrence date based on template settings
        $nextDate = $series->nextOccurrenceDate();
        $endDate = $nextDate->copy()->addHours($series->length);

        // initialize the form object with the values from the template
        $event = new Event(['name' => $series->name,
            'slug' => $series->slug,
            'short' => $series->short,
            'venue_id' => $series->venue_id,
            'series_id' => $series->id,
            'description' => $series->description,
            'event_type_id' => $series->event_type_id,
            'promoter_id' => $series->promoter_id,
            'soundcheck_at' => $series->soundcheck_at,
            'door_at' => $series->door_at,
            'start_at' => $nextDate,
            'end_at' => $endDate,
            'presale_price' => $series->presale_price,
            'door_price' => $series->door_price,
            'min_age' => $series->min_age,
            'visibility_id' => $series->visibility_id,
            'length' => 0,
        ]);

        return view('series.createOccurrence-tw', compact('seriesOptions', 'userOptions', 'event'))
        ->with($this->getSeriesFormOptions())
        ->with(['series' => $series]);
    }

    public function update(Series $series, SeriesRequest $request, SeriesWriter $writer): RedirectResponse
    {
        if ($this->user->cannot('update', $series)) {
            return $this->unauthorized($request);
        }

        $writer->replace($series, $this->user, $request->validated(), $request->input('tag_list', []), $request->input('entity_list', []));

        flash('Success', 'Your event series has been updated');

        // redirect to the show route with the slug
        return redirect()->route('series.show', compact('series'));
    }

    protected function unauthorized(SeriesRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'You do not have permission to edit this series.'], 403);
        }

        Session::flash('flash_message', ['title' => 'Access Denied', 'message' => 'You do not have permission to edit this series.', 'level' => 'error']);

        return redirect('/');
    }

    public function destroy(Series $series): RedirectResponse
    {
        $this->authorize('delete', $series);

        // add to activity log
        Activity::log($series, $this->user, 3);

        $series->delete();

        return redirect('series');
    }

    /**
     * Add a photo to a series.
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): void
    {
        // must be logged in
        if (!$this->user) {
            abort(401);
        }

        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp|max:5120', // KB; matches Dropzone maxFilesize
        ]);

        // attach to series
        if ($series = Series::find($id)) {

            // only the series owner (or an admin) may add photos
            if ($this->user->cannot('update', $series)) {
                abort(403);
            }

            // make the photo object from the file in the request
            $photo = $imageHandler->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if (isset($series->photos) && 0 === count($series->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to series
            $series->addPhoto($photo);
        }
    }

    /**
     * Mark user as following the series.
     *
     * @throws \Throwable
     */
    public function follow(int $id, Request $request): Response|RedirectResponse|array
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!($series = Series::find($id)) || !$series->isVisibleTo($this->user)) {
            flash()->error('Error', 'No such series');

            return back();
        }

        // add the following response; a double submit must not add a second row (#2341)
        $follow = Follow::firstOrCreate(['object_id' => $series->id, 'user_id' => $this->user->id, 'object_type' => 'series']);

        if ($follow->wasRecentlyCreated) {
            Log::info('User '.$id.' is following '.$series->name);

            // add to activity log
            Activity::log($series, $this->user, 6);
        }

        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the series - '.$series->name,
                'Success' => view('series.card-tw')
                    ->with(compact('series'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are now following the series - '.$series->name);

        return back();
    }

    /**
     * Mark user as unfollowing the series.
     *
     * @throws \Throwable
     */
    public function unfollow(int $id, Request $request): Response|RedirectResponse|array
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$series = Series::find($id)) {
            flash()->error('Error', 'No such series');

            return back();
        }

        // delete the follow, including any duplicates left by older double submits
        if (Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'series')->delete()) {
            // add to activity log
            Activity::log($series, $this->user, 7);
        }

        // the series may have gone private since: the follow still goes, but
        // the reply doesn't show the series
        if (!$series->isVisibleTo($this->user)) {
            if ($request->ajax()) {
                return ['Message' => 'You are no longer following the series.', 'Success' => ''];
            }
            flash()->success('Success', 'You are no longer following the series.');

            return back();
        }

        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the series - '.$series->name,
                'Success' => view('series.card-tw')
                    ->with(compact('series'))
                    ->render(),
            ];
        }

        flash()->success('Success', 'You are no longer following the series - '.$series->name);

        return back();
    }

    /**
     * The embed loader routes serve series; a series' embeds come from its
     * description and its entities' links.
     *
     * @return Builder<Series>
     */
    protected function embedQuery(): Builder
    {
        return Series::with('entities.links');
    }
}
