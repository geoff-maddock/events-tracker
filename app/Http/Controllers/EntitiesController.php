<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Http\Controllers\Concerns\LoadsEmbeds;
use App\Filters\EntityFilters;
use App\Http\Requests\EntityRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Http\Response\ResultSet\ListResultSet;
use App\Models\Activity;
use App\Models\Action;
use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Services\Embeds\OembedExtractor;
use App\Services\EntityStats;
use App\Services\ImageHandler;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\Integrations\InstagramEntityPoster;
use App\Services\StringHelper;
use App\Services\EntityDuplicateFinder;
use App\Services\EntitySummaryResult;
use App\Services\EntityUpdateSummarySender;
use App\Services\EntityWriter;
use App\Services\OptionLists;
use App\Services\TempImageStore;
use Carbon\Carbon;
use RuntimeException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EntitiesController extends Controller
{
    use BuildsListPages;
    use LoadsEmbeds;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected EntityFilters $filter;

    public function __construct(EntityFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'follow', 'destroy']]);
        // like events and series: only a verified account adds or edits content (#2312)
        $this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update', 'quickStore']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.entities.';

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['entities.created_at' => 'desc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

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
        $listResultSet = $this->startEntityList(
            $request,
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_entity_index',
            $this->getBaseQuery(),
            $this->defaultSortCriteria,
            defaultFilters: ['entity_status' => 'Active'],
        );

        return $this->entityListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * Start an entity list page (#2406): every entity list shares one session
     * base index, the entity filters, the popularity score (events in the
     * list's active_range filter, plus follows) and the "popularity" sort name.
     *
     * @param Builder<Entity> $baseQuery
     * @param array<string, string> $defaultSort
     * @param array<string, mixed> $defaultFilters
     * @param array<string, mixed> $parentFilter
     */
    private function startEntityList(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $keyPrefix,
        Builder $baseQuery,
        array $defaultSort = ['entities.name' => 'asc'],
        array $defaultFilters = [],
        array $parentFilter = [],
    ): ListResultSet {
        // the active range may come from this list's saved filters
        $this->useList($listParamSessionStore, 'internal_entity', $keyPrefix);
        $activeRange = $this->getActiveRangeFilter($request, $listParamSessionStore);

        return $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_entity',
            $keyPrefix,
            action([EntitiesController::class, 'index']),
            $this->filter,
            $this->addPopularityScoreToQuery($baseQuery, $activeRange),
            $defaultSort,
            $defaultFilters,
            $parentFilter,
            sortAliases: ['popularity' => 'popularity_score'],
        );
    }

    /**
     * Render the entity list view for a started list.
     *
     * @param array<string, mixed> $extra more view data (the page's role, type, tag, ...)
     * @param array<string, mixed>|false $playerFilters filters the page applies itself, for the
     *                                                   "Play this list" queue; false hides the button
     *
     * @throws \Throwable
     */
    private function entityListPage(
        ListParameterSessionStore $listParamSessionStore,
        ListResultSet $listResultSet,
        array $extra = [],
        array|false $playerFilters = [],
    ): string {
        $entities = $listResultSet->getList()
            // the card relations include roles, which index-json-ld's EntitySchema::type() checks
            // per entity; without it that's an N+1 query per row (EVENTREPO-VN).
            ->with(Entity::CARD_EAGER_LOAD)
            ->paginate($listResultSet->getLimit());

        $extra['playerQuery'] = $playerFilters === false ? null : $this->playerQuery($listResultSet, $playerFilters);

        return view('entities.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet, extra: $extra))
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display a listing of the resource that the user is following.
     *
     * @throws \Throwable
     */
    public function indexFollowing(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $baseQuery = $this->getBaseQuery()
            ->join('follows', 'entities.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'entity')
            ->where('follows.user_id', '=', $this->user->id);

        $listResultSet = $this->startEntityList(
            $request,
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_entity_index',
            $baseQuery,
            $this->defaultSortCriteria,
        );

        return $this->entityListPage($listParamSessionStore, $listResultSet, ['type' => 'Following'], ['display_type' => 'following']);
    }

    /**
     * Entities with their type joined in, for the sort by entity type.
     *
     * @return Builder<Entity>
     */
    public function getBaseQuery(): Builder
    {
        return Entity::query()->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');
    }

    /**
     * Display a listing of entities by type.
     *
     * @throws \Throwable
     */
    public function indexTypes(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $type
    ): string {
        $listResultSet = $this->startEntityList(
            $request,
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_entity_roles',
            $this->getBaseQuery(),
            parentFilter: ['entity_type' => $type],
        );

        return $this->entityListPage($listParamSessionStore, $listResultSet, ['type' => $type]);
    }

    /**
     * Display a listing of entities by role.
     *
     * @throws \Throwable
     */
    public function indexRoles(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        ?string $role
    ): string {
        $listResultSet = $this->startEntityList(
            $request,
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_entity_roles',
            $this->getBaseQuery(),
            ['popularity_score' => 'desc'],
            parentFilter: ['role' => $role],
        );

        return $this->entityListPage($listParamSessionStore, $listResultSet, ['role' => $role]);
    }

    /**
     * Filter the list of entities.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $baseQuery = $this->getBaseQuery()
            ->leftJoin('entity_statuses', 'entities.entity_status_id', '=', 'entity_statuses.id');

        $listResultSet = $this->startEntityList($request, $listParamSessionStore, $listEntityResultBuilder, 'internal_entity_index', $baseQuery);

        return $this->entityListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * Reset the rpp, sort, order.
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_entity', $request->get('key') ?? 'internal_entity_index', filters: false);

        return redirect()->route('entities.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): Response {
        $this->clearList($listParamSessionStore, 'internal_entity', $request->get('key') ?? 'internal_entity_index', filters: true);

        return redirect()->route('entities.index');
    }

    /**
     * Apply filters from URL query parameters and redirect to filtered view.
     * This allows users to share filtered entity lists via URL.
     *
     * @return RedirectResponse
     */
    public function applyFilterFromUrl(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // Initialize session store
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // Set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

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

        // Redirect to entities filter page to apply the filters
        return redirect()->route('entities.filter');
    }

    /**
     * Display a listing of entities by tag.
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
        // Get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $listResultSet = $this->startEntityList(
            $request,
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_entity_tags',
            $this->getBaseQuery(),
            parentFilter: ['tag' => $slug],
        );

        return $this->entityListPage($listParamSessionStore, $listResultSet, ['tag' => $tag]);
    }

    /**
     * Display a listing of entities by alias.
     *
     * @throws \Throwable
     */
    public function indexAliases(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $alias
    ): string {
        $baseQuery = Entity::getByAlias($alias)->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');

        $listResultSet = $this->startEntityList(
            $request,
            $listParamSessionStore,
            $listEntityResultBuilder,
            // shares its filters and sort with the tag list, as it always has
            'internal_entity_tags',
            $baseQuery,
        );

        // no alias filter for the player to mirror
        return $this->entityListPage($listParamSessionStore, $listResultSet, ['role' => $alias], playerFilters: false);
    }

    /**
     * Display a specific entity by role and slug.
     * This provides semantic routes like /venue/brillobox or /dj/cutups
     *
     * @throws \Throwable
     */
    public function showByRoleAndSlug(string $slug, string $role, Request $request, EntityStats $stats)
    {
        // Get entity by slug and verify it has the specified role
        /** @var Entity|null $entity */
        $entity = Entity::getBySlug(strtolower($slug))
            ->whereHas('roles', function ($q) use ($role) {
                $q->where('slug', '=', strtolower($role));
            })
            ->first();

        if (!$entity) {
            abort(404);
        }

        $stats->recordView($entity, $request, $request->user());

        // eager-load relations consumed by the page + JSON-LD
        $entity->loadMissing(['roles', 'aliases', 'photos', 'tags', 'locations.visibility', 'links', 'entityType']);

        // get related events (up to 12, sorted by date descending)
        $relatedEvents = $entity->events()
            ->visible($this->user)
            ->with([
                'venue.locations',
                'venue.links',
                'venue.photos',
                'promoter.links',
                'series.photos',
                'photos',
                'entities.roles',
                'entities.links',
                // a short run's dates, for its JSON-LD (#2375)
                'showings',
                'eventStatus',
                'visibility',
            ])
            ->orderBy('start_at', 'desc')
            ->limit(12)
            ->get();

        return view('entities.show-tw')
            ->with(compact('entity', 'relatedEvents'))
            ->render();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response|string
     */
    public function create()
    {
        return view('entities.create')->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EntityRequest $request, EntityWriter $writer, TempImageStore $tempImages): Response
    {
        // validated() leaves out the photo-attachment tokens and anything else
        // the form carries that isn't an entity attribute
        $entity = $writer->create(
            $this->user,
            $request->validated(),
            (array) $request->input('tag_list', []),
            (array) $request->input('alias_list', []),
            (array) $request->input('role_list', [])
        );

        $this->syncOwnersFromRequest($entity, $request);

        // Attach the image the user chose on the create form, whether or not
        // they ran it through analysis, as the primary photo.
        $tempImages->attachFromRequest($request, $entity);

        flash()->success('Success', 'Your entity has been created');
        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Quickly create a minimal entity (venue or promoter) from the event form and return JSON.
     * Lets users add a missing venue/promoter inline without leaving the event form.
     */
    public function quickStore(Request $request, ImageHandler $imageHandler, EntityWriter $writer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'role' => 'nullable|string',
            'short' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ]);

        $role = in_array($request->input('role'), ['Venue', 'Promoter'], true)
            ? $request->input('role')
            : 'Venue';

        $entity = $writer->quickCreate($this->user, $validated['name'], $role, $validated['short'] ?? null, $validated['description'] ?? null);

        if ($request->hasFile('image')) {
            $photo = $imageHandler->makePhoto($request->file('image'));
            $photo->is_primary = 1;
            $photo->save();
            $entity->addPhoto($photo);
        }

        return response()->json([
            'id' => $entity->id,
            'name' => $entity->name,
            'slug' => $entity->slug,
        ], 201);
    }

    /**
     * Check for existing entities whose name or alias matches the one being entered,
     * so the event quick-add modal and the entity form can warn about likely duplicates.
     */
    public function quickCheck(Request $request, EntityDuplicateFinder $finder): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            // Restrict matches to one type: the entity form only warns about a
            // same-name-same-type collision, since the same name legitimately
            // exists across types (a band and the venue named after it).
            'entity_type_id' => 'nullable|integer|exists:entity_types,id',
            // When editing, the entity being edited must not match itself.
            'exclude_id' => 'nullable|integer',
        ]);

        $matches = $finder->matches($validated['name'], $validated['entity_type_id'] ?? null, $validated['exclude_id'] ?? null);

        return response()->json(['data' => $matches]);
    }

    /**
     * The entity's events the viewer may see. Venue pages need events linked
     * either through the entity_event pivot (billed as a performer/promoter at
     * their own room) OR via the event's direct venue_id: many events are only
     * ever linked by venue_id, so a pivot-only query silently drops them from
     * the venue's own page. Other entities use the pivot only.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Event>|\Illuminate\Database\Eloquent\Relations\BelongsToMany<Event, Entity>
     */
    private function entityEvents(Entity $entity)
    {
        if (!$entity->hasRole('Venue')) {
            return $entity->events()->visible($this->user);
        }

        return Event::where(function ($query) use ($entity) {
            $query->whereHas('entities', function ($q) use ($entity) {
                $q->where('entities.id', $entity->id);
            })->orWhere('venue_id', $entity->id);
        })
            ->where(fn ($q) => $q->visible($this->user))
            ->distinct();
    }

    /**
     * Who the entity performs with, and where (not for venues and shops):
     * ranked over the last two years, falling back to all time when there's
     * nothing recent. Each comes with whether it is the recent ranking.
     *
     * @return array{with: \Illuminate\Database\Eloquent\Collection<int, Entity>, withIsRecent: bool, at: \Illuminate\Database\Eloquent\Collection<int, Entity>|null, atIsRecent: bool}
     */
    private function performanceRankings(Entity $entity): array
    {
        $with = $entity->getRecentlyPerformsWith(10, $this->user);
        $withIsRecent = $with->isNotEmpty();
        if (!$withIsRecent) {
            $with = $entity->getFrequentlyPerformsWith(10, $this->user);
        }

        if ($entity->hasRole('Venue') || $entity->hasRole('Shop')) {
            return ['with' => $with, 'withIsRecent' => $withIsRecent, 'at' => null, 'atIsRecent' => false];
        }

        $at = $entity->getRecentlyPerformsAt(10, $this->user);
        $atIsRecent = $at->isNotEmpty();
        if (!$atIsRecent) {
            $at = $entity->getFrequentlyPerformsAt(10, $this->user);
        }

        return ['with' => $with, 'withIsRecent' => $withIsRecent, 'at' => $at, 'atIsRecent' => $atIsRecent];
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity, OembedExtractor $embedExtractor, Request $request, EntityStats $stats): View
    {
        app('redirect')->setIntendedUrl(url()->current());

        // the binding already hides unlisted entities from non-admins; this guards other ways in
        if (!$entity->isVisibleTo($this->user)) {
            abort(404);
        }

        $stats->recordView($entity, $request, $request->user());

        // eager-load relations consumed by the page + JSON-LD (App\Services\EntitySchema)
        $entity->loadMissing(['roles', 'aliases', 'photos', 'tags', 'locations.visibility', 'links', 'entityType']);

        // get the threads for the entity
        $threads = $entity->threads()->visible($this->user)->paginate($this->limit);

        // pass empty embeds here - this was moved to a deferred ajax load
        $embeds = [];

        // track streaming isn't wired up yet
        $tracks = [];

        // upcoming events (up to 16, soonest first)
        $relatedEvents = $this->entityEvents($entity)
            ->with([
                'venue.locations',
                'venue.links',
                'venue.photos',
                'promoter.links',
                'series.photos',
                'photos',
                'entities.roles',
                'entities.links',
                // a short run's dates, for its JSON-LD (#2375)
                'showings',
                'eventStatus',
                'visibility',
                'eventType',
                'tags',
            ])
            // a short run stays until its last showing, placed by its next one (#2375)
            ->occurringBetween(Carbon::today()->startOfDay())
            ->withNextStart(Carbon::today()->startOfDay())
            ->orderByNextStart(Carbon::today()->startOfDay())
            ->limit(16)
            ->get();

        // past events (prior to today, most recent first); fetch 21 to detect overflow past the 20 shown
        // Eager-load the relations the past-events grid card reads per event
        // (primary photo, venue, event type, tags) to avoid an N+1.
        $pastEvents = $this->entityEvents($entity)
            ->with(['eventType', 'tags', 'venue', 'photos'])
            ->where('events.last_start_at', '<', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'desc')
            ->limit(21)
            ->get();

        [
            'with' => $performsWith,
            'withIsRecent' => $performsWithIsRecent,
            'at' => $performsAt,
            'atIsRecent' => $performsAtIsRecent,
        ] = $this->performanceRankings($entity);

        $recentlyTagged = $entity->getRecentlyTagged(10, $this->user);

        return view('entities.show-tw', compact('entity', 'threads', 'embeds', 'tracks', 'relatedEvents', 'pastEvents', 'performsWith', 'performsWithIsRecent', 'performsAt', 'performsAtIsRecent', 'recentlyTagged'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity): View|RedirectResponse
    {
        if ($this->user->cannot('update', $entity)) {
            \Session::flash('flash_message', ['title' => 'Access Denied', 'message' => 'You do not have permission to edit this entity.', 'level' => 'error']);

            return redirect()->route('entities.show', compact('entity'));
        }

        return view('entities.edit', compact('entity'))
        ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Entity $entity, EntityRequest $request, EntityWriter $writer): RedirectResponse|Response
    {
        if ($this->user->cannot('update', $entity)) {
            return $this->unauthorized($request);
        }

        // created_by records who added the entity and has no rule, so validated() never
        // carries it; ownership changes go through owner_list
        $writer->replace(
            $entity,
            $this->user,
            $request->validated(),
            (array) $request->input('tag_list', []),
            (array) $request->input('alias_list', []),
            (array) $request->input('role_list', [])
        );

        $msg = 'Updated entity. ';
        foreach ($writer->added() as $added) {
            $msg .= ' Added '.$added.'.';
        }
        $msg .= $this->syncOwnersFromRequest($entity, $request);

        flash()->success('Success', $msg);

        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity): RedirectResponse
    {
        $this->authorize('delete', $entity);

        // add to activity log
        Activity::log($entity, $this->user, Action::DELETE);

        $entity->delete();

        return redirect('entities');
    }

    /**
     * Add a photo to an entity.
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

        // attach to entity
        if ($entity = Entity::find($id)) {

            // only the entity owner (or an admin) may add photos
            if ($this->user->cannot('update', $entity)) {
                abort(403);
            }

            $photo = $imageHandler->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if (isset($entity->photos) && 0 === count($entity->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to entity
            $entity->addPhoto($photo);
        }
    }


    /**
     * Mark user as following the entity.
     *
     * @return Response|JsonResponse|RedirectResponse|array
     *
     * @throws \Throwable
     */
    public function follow(int $id, Request $request)
    {
        // check if there is a logged in user (AJAX callers like the audio player get a status, not a redirect)
        if (!$this->user) {
            if ($request->ajax()) {
                return response()->json(['Message' => 'No user is logged in.'], 401);
            }
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        // check if the entity exists
        if (!$entity = Entity::find($id)) {
            if ($request->ajax()) {
                return response()->json(['Message' => 'No such entity'], 404);
            }
            flash()->error('Error', 'No such entity');

            return back();
        }

        // check if the user already follows; for AJAX that's the state asked for, so it succeeds
        if ($entity->followedBy($this->user) !== null) {
            if ($request->ajax()) {
                return ['Message' => 'You are already following '.$entity->name, 'following' => true];
            }
            flash()->error('Error', 'You are already following '.$entity->name);

            return redirect()->route('entities.show', compact('entity'));
        }

        // add the following response
        // firstOrCreate: two clicks at once must not trip the one-follow-per-user index
        $follow = Follow::firstOrCreate(['object_type' => 'entity', 'object_id' => $id, 'user_id' => $this->user->id]);

        Log::info('User '.$id.' is following '.$entity->name);

        // add to activity log
        Activity::log($entity, $this->user, Action::FOLLOW);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the entity - '.$entity->name,
                'following' => true,
                'Success' => view('entities.card-tw')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are now following the entity - '.$entity->name);

        return redirect()->intended('/entities/'.$entity->slug);
    }

    /**
     * Mark user as unfollowing the entity.
     *
     * @return Response|JsonResponse|RedirectResponse|array
     *
     * @throws \Throwable
     */
    public function unfollow(int $id, Request $request)
    {
        // check if there is a logged in user (AJAX callers like the audio player get a status, not a redirect)
        if (!$this->user) {
            if ($request->ajax()) {
                return response()->json(['Message' => 'No user is logged in.'], 401);
            }
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$entity = Entity::find($id)) {
            if ($request->ajax()) {
                return response()->json(['Message' => 'No such entity'], 404);
            }
            flash()->error('Error', 'No such entity');

            return back();
        }

        // delete the follow, including any duplicates left by older double submits (#2341)
        if (Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'entity')->delete()) {
            // add to activity log
            Activity::log($entity, $this->user, Action::UNFOLLOW);
        }

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the entity - '.$entity->name,
                'following' => false,
                'Success' => view('entities.card-tw')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are no longer following the entity - '.$entity->name);

        return back();
    }

    /**
     * Post an entity, with its upcoming events, to the Instagram feed.
     */
    public function postToInstagram(int $id, InstagramEntityPoster $poster): RedirectResponse
    {
        return $this->postEntityToInstagram($id, fn (Entity $entity) => $poster->postToFeed($entity, $this->user));
    }

    /**
     * Post an entity to an Instagram story.
     */
    public function postStoryToInstagram(int $id, InstagramEntityPoster $poster): RedirectResponse
    {
        return $this->postEntityToInstagram($id, fn (Entity $entity) => $poster->postStory($entity, $this->user));
    }

    /**
     * Find the entity, check the user may post it, run the post and flash the result.
     *
     * @param callable(Entity): int $post
     */
    private function postEntityToInstagram(int $id, callable $post): RedirectResponse
    {
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // same people who see the menu link: those who may edit the entity
        if (!$this->user || !$this->user->can('update', $entity)) {
            flash()->error('Error', 'You are not authorized to post this entity to Instagram.');

            return back();
        }

        try {
            $result = $post($entity);
        } catch (RuntimeException $e) {
            flash()->error('Error', $e->getMessage());

            return back();
        }

        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
    }

    /**
     * Send an update summary email to the entity's contact email address.
     */
    public function sendUpdateSummary(int $id, EntityUpdateSummarySender $sender): RedirectResponse
    {
        // only super_admins may trigger this action
        if (!$this->user?->isSuperAdmin()) {
            flash()->error('Error', 'You are not authorized to perform this action.');

            return back();
        }

        if (!$entity = Entity::with(['contacts', 'entityType', 'entityStatus', 'roles', 'tags'])->find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // Sending the summary is the point of this action — do not claim it
        // was delivered when the transport rejected it.
        $result = $sender->send($entity);
        match ($result->outcome) {
            EntitySummaryResult::NO_CONTACT => flash()->error('Error', 'This entity has no contact email address on file.'),
            EntitySummaryResult::OPTED_OUT => flash()->error('Email not sent', $result->email.' has unsubscribed from entity emails.'),
            EntitySummaryResult::FAILED => flash()->error('Email not sent', 'The update summary to '.$result->email.' could not be sent.'),
            default => flash()->success('Success', 'Update summary sent to '.$result->email),
        };

        return back();
    }

    /**
     * Apply the owners panel on the entity form. Only users who can grant
     * entity ownership see the panel, and only its presence triggers a sync,
     * so a form without it never clears the owners.
     */
    protected function syncOwnersFromRequest(Entity $entity, Request $request): string
    {
        if (!$request->boolean('manage_owners') || $this->user->cannot('grant_entity_ownership')) {
            return '';
        }

        $before = $entity->owners()->pluck('users.id')->sort()->values();
        $entity->syncOwners($request->input('owner_list', []), $this->user);
        $after = $entity->owners()->pluck('users.id')->sort()->values();

        if ($before->all() === $after->all()) {
            return '';
        }

        Activity::log($entity, $this->user, Action::UPDATE, 'Owners changed from ['.$before->implode(', ').'] to ['.$after->implode(', ').']');

        return ' Updated owners.';
    }

    protected function unauthorized(EntityRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'You do not have permission to edit this entity.'], 403);
        }

        \Session::flash('flash_message', ['title' => 'Access Denied', 'message' => 'You do not have permission to edit this entity.', 'level' => 'error']);

        return redirect('/');
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['entities.name' => 'Name', 'entity_types.name' => 'Entity Type', 'entities.created_at' => 'Created At', 'popularity_score' => 'Popularity'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    /**
     * Resolve the active_range filter value from request input or persisted session filters.
     */
    protected function getActiveRangeFilter(Request $request, ListParameterSessionStore $listParamSessionStore): ?string
    {
        $filters = $request->input('filters', $listParamSessionStore->getFilters() ?? []);
        if (!is_array($filters)) {
            return null;
        }

        $activeRange = $filters['active_range'] ?? null;

        return is_string($activeRange) ? $activeRange : null;
    }

    /**
     * Add the computed popularity_score select (follows + events in the active range).
     *
     * @param Builder<Entity> $query
     *
     * @return Builder<Entity>
     */
    protected function addPopularityScoreToQuery(Builder $query, ?string $activeRange): Builder
    {
        return $query->withPopularityScore($activeRange);
    }

    /**
     * The audio player queue for an entity list (#2366): the list's filters
     * (user, default and the page's own role/type/tag) and its sort, or null
     * when nothing on the list is playable, which hides the button.
     *
     * @param array<string, mixed> $filters extra filters the page applies itself
     *
     * @return array<string, mixed>|null
     */
    protected function playerQuery(ListResultSet $listResultSet, array $filters = []): ?array
    {
        $listed = (clone $listResultSet->getList())->reorder()->select('entities.id');
        // the whole list, not the page paginate() left on the query
        $listed->getQuery()->limit = null;
        $listed->getQuery()->offset = null;

        if (!Entity::query()->whereIn('entities.id', $listed)->playable()->exists()) {
            return null;
        }

        return array_filter([
            'source' => 'entities',
            'filters' => self::playerFilters(array_merge($listResultSet->getFilters(), $listResultSet->getParentFilters(), $filters)),
            'sort' => $listResultSet->getSort(),
            'direction' => $listResultSet->getSortDirection(),
        ], fn ($value) => $value !== '' && $value !== []);
    }

    /**
     * Filters without empty values, so the player link stays short.
     *
     * @param array<array-key, mixed> $filters
     *
     * @return array<array-key, mixed>
     */
    private static function playerFilters(array $filters): array
    {
        $kept = [];

        foreach ($filters as $name => $value) {
            if (is_array($value)) {
                $value = self::playerFilters($value);
            }

            if ($value !== null && $value !== '' && $value !== []) {
                $kept[$name] = $value;
            }
        }

        return $kept;
    }

    protected function getFilterOptions(): array
    {
        return [
            'tagOptions' => ['' => '&nbsp;'] + OptionLists::tags('slug'),
            'roleOptions' => ['' => ''] + Role::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'entityTypeOptions' => ['' => ''] + EntityType::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'entityStatusOptions' => ['' => ''] +  EntityStatus::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'entityTypeOptions' => ['' => ''] + EntityType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityStatusOptions' => EntityStatus::formOptionsFor($this->user),
            'tagOptions' => OptionLists::tags(),
            'aliasOptions' => Alias::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'roleOptions' => Role::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'userOptions' => ['' => ''] + OptionLists::users(),
        ];
    }

    /**
     * The embed loader routes serve entities; an entity's embeds come from its links.
     *
     * @return Builder<Entity>
     */
    protected function embedQuery(): Builder
    {
        return Entity::with('links');
    }
}
