<?php

namespace App\Http\Controllers\Api;

use App\Events\EventCreated;
use App\Jobs\NotifyFollowers;
use App\Events\EventPhotoAdded;
use App\Events\EventUpdated;
use App\Exceptions\RemoteImageException;
use App\Filters\EventFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventPatchRequest;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Http\Resources\MinimalResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventType;
use App\Models\Photo;
use App\Models\Tag;
use App\Services\Embeds\OembedExtractor;
use App\Services\ImageHandler;
use App\Services\RemoteImageFetcher;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class EventsController extends Controller
{
    /**
     * Relations the by-date event list returns (plus attendees, which needs a
     * constraint so it is added separately).
     */
    private const BY_DATE_EAGER_LOAD = [
        'visibility',
        'venue.links',
        'venue.photos',
        'venue.locations',
        'venue.entityStatus',
        'venue.entityType',
        'eventStatus',
        'eventType',
        'promoter.links',
        'promoter.photos',
        'promoter.locations',
        'promoter.entityStatus',
        'promoter.entityType',
        'series',
        'tags',
        'entities',
        'photos',
    ];

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
        $this->middleware('auth:sanctum')->only(['attendJson', 'unattendJson', 'store', 'update', 'destroy', 'indexAttending']);

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
        $events = $query
            ->visible($this->user)
            ->with([
                'visibility',
                'venue.links',
                'venue.photos',
                'venue.locations',
                'venue.entityStatus',
                'venue.entityType',
                'eventStatus',
                'eventType',
                'promoter.links',
                'promoter.photos',
                'promoter.locations',
                'promoter.entityStatus',
                'promoter.entityType',
                'series',
                'tags',
                'entities',
                'photos',
                'attendees' => function ($q) {
                    $q->where('response_type_id', 1);
                },
            ])
            ->paginate($listResultSet->getLimit());

        return response()->json(new EventCollection($events));
    }

    /**
     * Display a listing of the most popular events.
     */
    public function popular(Request $request): JsonResponse
    {
        $days = min(max((int) $request->get('days', 30), 1), 365);
        $limit = min(max((int) $request->get('limit', 30), 1), \App\Http\Requests\ListQueryParameters::MAX_LIMIT);
        $from = Carbon::now()->subDays($days);

        $query = Event::query()
            ->withCount([
                'attendees as attendees_count' => function ($q) use ($from) {
                    $q->where('event_responses.created_at', '>=', $from);
                },
            ])
            ->filter($this->filter);

        $events = $query
            ->visible($this->user)
            ->with([
                'visibility',
                'venue.links',
                'venue.photos',
                'venue.locations',
                'venue.entityStatus',
                'venue.entityType',
                'eventStatus',
                'eventType',
                'promoter.links',
                'promoter.photos',
                'promoter.locations',
                'promoter.entityStatus',
                'promoter.entityType',
                'series',
                'tags',
                'entities',
                'photos',
            ])
            ->orderByDesc('attendees_count')
            ->paginate($limit);

        $events->getCollection()->transform(function ($event) {
            $event->popularity_score = $event->attendees_count;

            return $event;
        });

        return response()->json(new EventCollection($events));
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function indexAttending(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // check who the authenticated user is
        $this->user = $request->user();
        if (!$this->user) {
            abort(403, 'Unauthorized action.');
        }

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
        $query = $listResultSet->getList();

        // get the events
        $events = $query
            ->with([
                'visibility',
                'venue.links',
                'venue.photos',
                'venue.locations',
                'venue.entityStatus',
                'venue.entityType',
                'eventStatus',
                'eventType',
                'promoter.links',
                'promoter.photos',
                'promoter.locations',
                'promoter.entityStatus',
                'promoter.entityType',
                'series',
                'tags',
                'entities',
                'photos',
                'attendees' => function ($q) {
                    $q->where('response_type_id', 1);
                },
                'threads'
            ])
            ->paginate($listResultSet->getLimit());

        return response()->json(new EventCollection($events));
    }

    /**
     * Display a listing of recommended events for the authenticated user.
     */
    public function indexRecommended(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $this->user = $request->user();
        if (!$this->user) {
            abort(403, 'Unauthorized action.');
        }

        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_recommended');
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        $baseQuery = $this->user->getRecommendedEvents()
            ->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')
            ->select('events.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Event> $query */
        $query = $listResultSet->getList();

        $events = $query
            ->visible($this->user)
            ->with([
                'visibility',
                'venue.links',
                'venue.photos',
                'venue.locations',
                'venue.entityStatus',
                'venue.entityType',
                'eventStatus',
                'eventType',
                'promoter.links',
                'promoter.photos',
                'promoter.locations',
                'promoter.entityStatus',
                'promoter.entityType',
                'series',
                'tags',
                'entities',
                'photos',
                'attendees' => function ($q) {
                    $q->where('response_type_id', 1);
                },
                'threads'
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
        [$start_at_from, $start_at_to] = $this->dateRange($year, $month, $day);

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_event');
        $listParamSessionStore->setKeyPrefix('internal_event_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EventsController::class, 'index']));

        // the list builder only supplies the page size here; the events come from the date query
        $listResultSet = $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder(Event::query()->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')->select('events.*'))
            ->setDefaultSort(['events.start_at' => 'desc'])
            ->listResultSetFactory();

        $events = Event::where('start_at', '>', $start_at_from)
            ->where('start_at', '<', $start_at_to)
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->with(self::BY_DATE_EAGER_LOAD)
            ->with(['attendees' => function ($q) {
                $q->where('response_type_id', 1);
            }])
            ->paginate($listResultSet->getLimit());

        return response()->json(new EventCollection($events));
    }

    /**
     * The [from, to) window a by-date request covers: a whole year, a month or
     * a day. Route params are cast to integers so single-digit months/days
     * (e.g. /api/events/by-date/2026/8) produce a valid date instead of an
     * unparseable string like "2026801" (EVENTREPO-WG).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(string $year, ?string $month, ?string $day): array
    {
        if ($year && !$month && !$day) {
            return [Carbon::create((int) $year, 1, 1)->startOfDay(), Carbon::create((int) $year, 12, 31)->startOfDay()];
        }

        if (!$day) {
            $from = Carbon::create((int) $year, (int) $month, 1)->startOfDay();

            return [$from, $from->copy()->endOfMonth()];
        }

        $from = Carbon::create((int) $year, (int) $month, (int) $day)->startOfDay();

        return [$from, $from->copy()->addDay()];
    }


 

    public function show(?Event $event, OembedExtractor $embedExtractor): JsonResponse
    {
        if (!$event || !$event->isVisibleTo($this->user)) {
            abort(404);
        }

        $event->load([
                'visibility',
                'venue.links',
                'venue.photos',
                'venue.locations',
                'venue.entityStatus',
                'venue.entityType',
                'eventStatus',
                'eventType',
                'promoter.links',
                'promoter.photos',
                'promoter.locations',
                'promoter.entityStatus',
                'promoter.entityType',
                'series',
                'tags',
                'entities',
                'photos',
        ]);

        return response()->json(new EventResource($event));
    }

    public function embeds(?Event $event,  OembedExtractor $embedExtractor): JsonResponse
    {
        if (!$event || !$event->isVisibleTo($this->user)) {
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

    public function minimalEmbeds(?Event $event, OembedExtractor $embedExtractor): JsonResponse
    {
        if (!$event || !$event->isVisibleTo($this->user)) {
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

        $input = $request->validated();

        // transform the slug passed in the request
        $input['slug'] = Str::slug($request->input('slug', '-'));
        
        // Set the user fields explicitly
        $input['created_by'] = $this->user->id;
        $input['updated_by'] = $this->user->id;

        // validation happening in EventRequest->rules
        $tagArray = $request->input('tag_list', []);
        $tags = Tag::resolveList($tagArray, $this->user);
        $syncArray = $tags->modelKeys();
        foreach ($tags->filter(fn (Tag $tag) => $tag->wasRecentlyCreated) as $tag) {
            $msg .= ' Added tag '.$tag->name.'.';
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
                NotifyFollowers::dispatch($event);
            }
        }

        return response()->json(new EventResource($event));
    }

    /**
     * PUT: full replacement of the resource.
     *
     * Optional fillable scalars omitted from the body are reset to null, and
     * relations (tags, entities) sync to the supplied arrays — missing keys
     * mean "detach all".
     */
    public function update(Event $event, EventRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->user = $request->user();

        if (!$event->ownedBy($this->user)) {
            return $this->unauthorized($request);
        }

        $input = $request->validated();

        // an owner may transfer the event, but a null created_by must not reach the
        // NOT NULL column (EVENTREPO-VM)
        if (empty($input['created_by'])) {
            unset($input['created_by']);
        }
        $input['updated_by'] = $this->user->id;

        $nonNullableBooleans = $this->nonNullableBooleanEventFields();
        foreach ($this->optionalEventFields() as $field) {
            $isNonNullableBoolean = in_array($field, $nonNullableBooleans, true);

            if (!array_key_exists($field, $input)) {
                // Boolean fields are backed by NOT NULL columns, so an omitted
                // field resets to false (0) rather than null to avoid an
                // integrity constraint violation.
                $input[$field] = $isNonNullableBoolean ? false : null;
            } elseif ($isNonNullableBoolean && null === $input[$field]) {
                // These columns carry no validation rule, so a body that sends
                // the key with an explicit null still reaches fill() as null and
                // trips the same NOT NULL constraint. Coerce it to false too
                // (EVENTREPO-XH).
                $input[$field] = false;
            }
        }

        $event->fill($input)->save();

        $event->tags()->sync($this->resolveTagIds($request->input('tag_list', [])));
        $event->entities()->sync($request->input('entity_list', []));

        Activity::log($event, $this->user, 2);
        EventUpdated::dispatch($event);

        return response()->json(new EventResource($event));
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched;
     * scalars and relations not in the request are left untouched.
     */
    public function patch(Event $event, EventPatchRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->user = $request->user();

        if (!$event->ownedBy($this->user)) {
            return $this->unauthorized($request);
        }

        $input = $request->validated();

        // an owner may transfer the event, but a null created_by must not reach the
        // NOT NULL column (EVENTREPO-VM)
        if (empty($input['created_by'])) {
            unset($input['created_by']);
        }

        $scalarInput = array_intersect_key($input, array_flip($event->getFillable()));
        if (!empty($scalarInput)) {
            $scalarInput['updated_by'] = $this->user->id;
            $event->fill($scalarInput)->save();
        }

        if ($request->has('tag_list')) {
            $event->tags()->sync($this->resolveTagIds((array) $request->input('tag_list', [])));
        }

        if ($request->has('entity_list')) {
            $event->entities()->sync((array) $request->input('entity_list', []));
        }

        Activity::log($event, $this->user, 2);
        EventUpdated::dispatch($event);

        return response()->json(new EventResource($event));
    }

    /**
     * Fillable scalar fields that aren't required by EventRequest. PUT resets
     * any omitted from the body to null.
     */
    private function optionalEventFields(): array
    {
        return [
            'short',
            'description',
            'event_status_id',
            'is_benefit',
            'promoter_id',
            'venue_id',
            'presale_price',
            'door_price',
            'ticket_link',
            'primary_link',
            'series_id',
            'soundcheck_at',
            'door_at',
            'end_at',
            'cancelled_at',
            'do_not_repost',
            'min_age',
        ];
    }

    /**
     * Subset of optional fields backed by NOT NULL boolean columns. When these
     * are omitted from a PUT they must reset to false, not null, otherwise the
     * update fails with an integrity constraint violation.
     *
     * @return array<int, string>
     */
    private function nonNullableBooleanEventFields(): array
    {
        return [
            'is_benefit',
            'do_not_repost',
        ];
    }

    /**
     * Resolve a list of tag identifiers to ids, creating any tags that don't
     * already exist by id. Accepts a mix of existing tag ids and new tag names.
     */
    private function resolveTagIds(array $tagArray): array
    {
        return Tag::resolveList($tagArray, $this->user)->modelKeys();
    }

    protected function unauthorized(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not authorized'], 403);
    }

    public function destroy(Event $event, Request $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->user->cannot('delete', $event)) {
            return $this->unauthorized($request);
        }

        // add to activity log
        Activity::log($event, $this->user, 3);

        $event->delete();

        return response()->json([], 204);
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

        return response()->json(new MinimalResource($event));
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

    public function photos(?Event $event): JsonResponse
    {
        if (!$event || !$event->isVisibleTo($this->user)) {
            abort(404);
        }

        $photos = [];

        // extract all the photos from the event
        $photoList = $event->photos()->get();

        foreach ($photoList as $photo) {
            $photos[] = $photo->getApiResponse();
        }

        return response()->json($photos);
    }

    public function allPhotos(?Event $event): JsonResponse
    {
        if (!$event || !$event->isVisibleTo($this->user)) {
            abort(404);
        }

        $photos = [];

        $photoList = $event->photos()->get();
        foreach ($photoList as $photo) {
            $photos[$photo->id] = $photo->getApiResponse();
        }

        $entities = $event->entities()->with('photos')->get();
        foreach ($entities as $entity) {
            foreach ($entity->photos as $photo) {
                $photos[$photo->id] = $photo->getApiResponse(false);
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
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp|max:5120', // KB; matches Dropzone maxFilesize
        ]);

        if (!$event = Event::find($id)) {
            return response()->json([], 404);
        }

        if ($denied = $this->denyUnlessCanAddPhoto($event)) {
            return $denied;
        }

        $photo = $imageHandler->makePhoto($request->file('file'));

        return $this->finishAddingPhoto($event, $photo);
    }

    /**
     * Attach a photo to an event from a public https URL. Identical to
     * addPhoto() except that the server downloads the bytes (see
     * RemoteImageFetcher for the SSRF guards).
     */
    public function addPhotoFromUrl(int $id, Request $request, ImageHandler $imageHandler, RemoteImageFetcher $fetcher): JsonResponse
    {
        $this->validate($request, [
            'url' => 'required|url',
        ]);

        if (!$event = Event::find($id)) {
            return response()->json([], 404);
        }

        if ($denied = $this->denyUnlessCanAddPhoto($event)) {
            return $denied;
        }

        try {
            $photo = $fetcher->fetch($request->input('url'), fn (UploadedFile $file) => $imageHandler->makePhoto($file));
        } catch (RemoteImageException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->finishAddingPhoto($event, $photo);
    }

    /**
     * Only the event owner (or an admin) may add photos — checked before
     * any bytes are fetched or written to storage.
     */
    private function denyUnlessCanAddPhoto(Event $event): ?JsonResponse
    {
        if (!$this->user
            || (!$event->ownedBy($this->user) && !$this->user->hasGroup('admin') && !$this->user->hasGroup('super_admin'))) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        return null;
    }

    /**
     * Shared tail of the upload and from-url paths: primary flag, attach,
     * dispatch and follower notification.
     */
    private function finishAddingPhoto(Event $event, Photo $photo): JsonResponse
    {
        // count existing photos BEFORE attaching; isset($event->photos)
        // used to cache the relation here, so the post-attach count read
        // stale data and the notification fired on the second photo
        $existingPhotoCount = $event->photos()->count();

        if (0 === $existingPhotoCount) {
            $photo->is_primary = 1;
        }

        $photo->save();
        $event->addPhoto($photo);

        EventPhotoAdded::dispatch($event, 0 === $existingPhotoCount);

        if ($event->start_at >= Carbon::now()) {
            // notify followers only when this is the event's first photo
            if (0 === $existingPhotoCount) {
                NotifyFollowers::dispatch($event);
            }
        }

        return response()->json($photo->getApiResponse(), 201);
    }
}
