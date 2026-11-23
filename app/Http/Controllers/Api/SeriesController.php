<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RemoteImageException;
use App\Http\Controllers\Controller;
use App\Filters\SeriesFilters;
use App\Http\Requests\SeriesPatchRequest;
use App\Http\Requests\SeriesRequest;
use App\Http\Resources\SeriesCollection;
use App\Http\Resources\SeriesResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Photo;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Visibility;
use App\Services\SeriesWriter;
use App\Services\ImageHandler;
use App\Services\RemoteImageFetcher;
use Carbon\Carbon;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SeriesController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected int $childLimit;

    protected array $defaultSortCriteria;

    protected bool $hasFilter;

    protected array $filters;

    protected int $page;

    // this is the class specifying the filters methods for each field
    protected SeriesFilters $filter;

    public function __construct(SeriesFilters $filter)
    {
        // like the web controllers: only a verified account writes content (#2314)
        $this->middleware('verified', ['only' => ['store', 'update', 'patch', 'addPhoto', 'addPhotoFromUrl']]);

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
        $this->hasFilter = false;

        $this->middleware('auth:sanctum')->only([
            'store',
            'followJson',
            'unfollowJson',
            'update',
            'destroy',
        ]);

        parent::__construct();
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
            // only series the caller may see; the default "public" filter can be overridden
            ->visible($this->user)
            ->select('series.*');
    }

    /**
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ):  JsonResponse {

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only series entities are returned
        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($this->baseQuery())
            ->setDefaultSort(['series.created_at' => 'desc'])
            ->setDefaultFilters(['visibility' => Visibility::VISIBILITY_PUBLIC]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $series = $query
            ->with(['occurrenceType', 'occurrenceWeek', 'occurrenceDay', 'visibility', 'eventStatus', 'eventType', 'promoter', 'venue', 'tags', 'entities', 'photos' => function ($query) {
                $query->where('photos.is_primary', '=', 1);
            }, 'upcomingEvent', 'upcomingPublicEvent' => function ($query) {
                $query->with(['venue.links', 'promoter.links', 'entities', 'tags', 'photos', 'series', 'eventType', 'eventStatus', 'visibility']);
            }])
            ->paginate($listResultSet->getLimit());

        return response()->json(new SeriesCollection($series));
    }

    /**
     * Display a listing of the most popular series.
     */
    public function popular(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $limit = (int) $request->get('limit', 30);
        $from = Carbon::now()->subDays($days);

        $query = Series::query()->filter($this->filter)
            ->visible($this->user)
            // attendance is counted on public events only
            ->leftJoin('events', function ($join) use ($from) {
                $join->on('series.id', '=', 'events.series_id')
                    ->where('events.start_at', '>=', $from)
                    ->where('events.visibility_id', '=', Visibility::VISIBILITY_PUBLIC);
            })
            ->leftJoin('event_responses', function ($join) {
                $join->on('events.id', '=', 'event_responses.event_id')
                    ->where('event_responses.response_type_id', 1);
            })
            ->select('series.*', DB::raw('COUNT(event_responses.id) as attendees_count'))
            ->groupBy('series.id');

        $series = $query
            ->with(['occurrenceType', 'occurrenceWeek', 'occurrenceDay', 'visibility', 'eventStatus', 'eventType', 'promoter', 'venue', 'tags', 'entities', 'photos' => function ($query) {
                $query->where('photos.is_primary', '=', 1);
            }, 'upcomingEvent', 'upcomingPublicEvent' => function ($query) {
                $query->with(['venue.links', 'promoter.links', 'entities', 'tags', 'photos', 'series', 'eventType', 'eventStatus', 'visibility']);
            }])
            ->orderByDesc('attendees_count')
            ->paginate($limit);

        $series->getCollection()->transform(function ($item) {
            $item->popularity_score = $item->attendees_count;

            return $item;
        });

        return response()->json(new SeriesCollection($series));
    }

    public function allPhotos(?Series $series): JsonResponse
    {
        // 404 rather than 403 so private and proposal series can't be probed
        if (!$series || !$series->isVisibleTo($this->user)) {
            abort(404);
        }

        $photos = [];

        $photoList = $series->photos()->get();
        foreach ($photoList as $photo) {
            $photos[$photo->id] = $photo->getApiResponse();
        }

        $entities = $series->entities()->with('photos')->get();
        foreach ($entities as $entity) {
            foreach ($entity->photos as $photo) {
                $photos[$photo->id] = $photo->getApiResponse(false);
            }
        }

        return response()->json(array_values($photos));
    }


    public function show(Series $series): JsonResponse
    {
        abort_unless($series->isVisibleTo($this->user), 404);

        $events = $series->events()->paginate($this->childLimit);
        $threads = $series->threads()->paginate($this->childLimit);

        return response()->json(new SeriesResource($series));
    }

    public function store(SeriesRequest $request, SeriesWriter $writer): JsonResponse
    {
        $this->user = $request->user();

        // through the API a series always belongs to whoever creates it
        $input = $request->validated();
        unset($input['created_by']);

        $series = $writer->create(
            $this->user,
            $input,
            $request->input('tag_list', []),
            (array) $request->input('entity_list', []),
            $request->eventLinkId ? (int) $request->eventLinkId : null,
        );

        return response()->json(new SeriesResource($series));
    }

    /**
     * PUT: full replacement of the resource.
     *
     * Optional fillable scalars omitted from the body are reset to null, and
     * relations (tags, entities) sync to the supplied arrays — missing keys
     * mean "detach all".
     */
    public function update(Series $series, SeriesRequest $request, SeriesWriter $writer): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->user->cannot('update', $series)) {
            return $this->unauthorized($request);
        }

        $input = $request->validated();
        foreach ($this->optionalSeriesFields() as $field) {
            if (!array_key_exists($field, $input)) {
                $input[$field] = null;
            }
        }

        $writer->replace($series, $this->user, $input, $request->input('tag_list', []), $request->input('entity_list', []));

        return response()->json(new SeriesResource($series));
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched;
     * scalars and relations not in the request are left untouched.
     */
    public function patch(Series $series, SeriesPatchRequest $request, SeriesWriter $writer): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->user->cannot('update', $series)) {
            return $this->unauthorized($request);
        }

        $writer->patch(
            $series,
            $this->user,
            $request->validated(),
            $request->has('tag_list') ? (array) $request->input('tag_list', []) : null,
            $request->has('entity_list') ? (array) $request->input('entity_list', []) : null,
        );

        return response()->json(new SeriesResource($series));
    }

    /**
     * Fillable scalar fields that aren't required by SeriesRequest. PUT resets
     * any omitted from the body to null (hold_date, NOT NULL, becomes false via
     * Series::setHoldDateAttribute). `created_by` is excluded because it tracks
     * ownership and shouldn't be cleared on update.
     */
    private function optionalSeriesFields(): array
    {
        return [
            'description',
            'occurrence_week_id',
            'occurrence_day_id',
            'promoter_id',
            'venue_id',
            'presale_price',
            'door_price',
            'soundcheck_at',
            'founded_at',
            'cancelled_at',
            'door_at',
            'start_at',
            'end_at',
            'length',
            'min_age',
            'hold_date',
            'primary_link',
            'ticket_link',
            'facebook_username',
            'instagram_username',
            'twitter_username',
        ];
    }

    protected function unauthorized(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not authorized'], 403);
    }

    public function destroy(Series $series, Request $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->user->cannot('delete', $series)) {
            return $this->unauthorized($request);
        }

        // add to activity log
        Activity::log($series, $this->user, 3);

        $series->delete();

        return response()->json([], 204);
    }

    /**
     * Add a photo to a series.
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): JsonResponse
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp|max:5120', // KB; matches Dropzone maxFilesize
        ]);

        if (!$series = Series::find($id)) {
            return response()->json([], 404);
        }

        if ($denied = $this->denyUnlessCanAddPhoto($series)) {
            return $denied;
        }

        $photo = $imageHandler->makePhoto($request->file('file'));

        return $this->finishAddingPhoto($series, $photo);
    }

    /**
     * Attach a photo to a series from a public https URL. Identical to
     * addPhoto() except that the server downloads the bytes (see
     * RemoteImageFetcher for the SSRF guards).
     */
    public function addPhotoFromUrl(int $id, Request $request, ImageHandler $imageHandler, RemoteImageFetcher $fetcher): JsonResponse
    {
        $this->validate($request, [
            'url' => 'required|url',
        ]);

        if (!$series = Series::find($id)) {
            return response()->json([], 404);
        }

        if ($denied = $this->denyUnlessCanAddPhoto($series)) {
            return $denied;
        }

        try {
            $photo = $fetcher->fetch($request->input('url'), fn (UploadedFile $file) => $imageHandler->makePhoto($file));
        } catch (RemoteImageException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->finishAddingPhoto($series, $photo);
    }

    /**
     * Only the series owner (or an admin) may add photos — checked before
     * any bytes are fetched or written to storage.
     */
    private function denyUnlessCanAddPhoto(Series $series): ?JsonResponse
    {
        if (!$this->user || $this->user->cannot('update', $series)) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        return null;
    }

    /**
     * Shared tail of the upload and from-url paths: primary flag and attach.
     */
    private function finishAddingPhoto(Series $series, Photo $photo): JsonResponse
    {
        // count existing photos BEFORE attaching, and if zero, make this primary
        if (0 === $series->photos()->count()) {
            $photo->is_primary = 1;
        }

        $photo->save();
        $series->addPhoto($photo);

        return response()->json($photo->getApiResponse(), 201);
    }

    /**
     * Follow the series and return JSON.
     */
    public function followJson(Series $series, Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($series->isVisibleTo($user), 404);

        $follow = Follow::firstOrCreate(['object_type' => 'series', 'object_id' => $series->id, 'user_id' => $user->id]);

        if ($follow->wasRecentlyCreated) {
            Activity::log($series, $user, 6);
        }

        return response()->json(new SeriesResource($series));
    }

    /**
     * Unfollow the series and return JSON.
     */
    public function unfollowJson(Series $series, Request $request): JsonResponse
    {
        $user = $request->user();

        $deleted = Follow::where('object_type', 'series')
            ->where('object_id', $series->id)
            ->where('user_id', $user->id)
            // every matching row, including duplicates left by older double submits
            ->delete();

        if ($deleted) {
            Activity::log($series, $user, 7);
        }

        return response()->json([], 204);
    }

    public function photos(?Series $series): JsonResponse
    {
        // 404 rather than 403 so private and proposal series can't be probed
        if (!$series || !$series->isVisibleTo($this->user)) {
            abort(404);
        }

        $photos = [];

        // extract all the links from the series
        $photoList = $series->photos()->get();

        foreach ($photoList as $photo) {
            $photos[] = $photo->getApiResponse();
        }

        return response()->json($photos);
    }

}
