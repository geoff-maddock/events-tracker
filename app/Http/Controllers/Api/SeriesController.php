<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\SeriesFilters;
use App\Http\Requests\SeriesRequest;
use App\Http\Resources\SeriesCollection;
use App\Http\Resources\SeriesResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Follow;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Photo;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\ImageHandler;
use App\Services\RssFeed;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\StringHelper;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Str;
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
        //$this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update']]);
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
            ->select('series.*');
    }

    /**
     * Reset the rpp, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_series_index';
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('api.series.index');
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
        $keyPrefix = $request->get('key') ?? 'internal_series_index';
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'api.series.index');
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
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        return response()->json(new SeriesCollection($series));
    }

    /**
     * @throws \Throwable
     */
    public function indexFollowing(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        $baseQuery = Series::join('follows', 'series.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'series')
            ->where('follows.user_id', '=', $this->user->id)
            ->orderBy('follows.created_at', 'desc')
            ->select('series.*');

        // create the base query including any required joins; needs select to make sure only series entities are returned
        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['series.created_at' => 'desc'])
            ->setDefaultFilters(['visibility' => Visibility::VISIBILITY_PUBLIC]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $series = $query
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('series.index')
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
            ->with(['type' => 'Following'])
            ->with(compact('series'))
            ->render();
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
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_cancelled');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only series entities are returned
        $baseQuery = $this->baseQuery()->whereNotNull('cancelled_at');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['series.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $series = $query
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('series.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                    'slug' => 'Cancelled',
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('series'))
            ->render();
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
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_cancelled');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        // this is more complex because we want to show weeklies that fall on the days, plus monthlies that fall on the days
        // may be an iterative process that is called from the template to the series model that checks against each criteria and builds a list that way
        // @phpstan-ignore-next-line
        $baseQuery = $this->baseQuery()->future();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['series.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $series = $query
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('series.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                    'slug' => 'Week',
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('series'))
            ->render();
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

        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_related');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($this->baseQuery())
            ->setDefaultSort(['series.created_at' => 'desc'])
            ->setParentFilter(['related' => $slug]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        // @phpstan-ignore-next-line
        $series = $query->visible($this->user)
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        return view('series.index')
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
            ->with(compact('series'))
            ->with(compact('related'))
            ->render();
    }

    /**
     * Display a listing of events by tag.
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

        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_tags');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($this->baseQuery())
            ->setDefaultSort(['series.created_at' => 'desc'])
            ->setParentFilter(['tag' => $slug]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $series = $query
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('series.index')
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
            ->with(compact('series'))
            ->with(compact('tag'))
            ->render();
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


    public function show(Series $series): JsonResponse
    {
        $events = $series->events()->paginate($this->childLimit);
        $threads = $series->threads()->paginate($this->childLimit);

        return response()->json(new SeriesResource($series));
    }

    public function store(SeriesRequest $request, Series $series): JsonResponse
    {
        $msg = '';
        $input = $request->all();

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (count(DB::table('tags')->where('id', $tag)->get()) > 0) {
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

        $series = $series->create($input);

        $series->tags()->attach($syncArray);
        $series->entities()->attach($request->input('entity_list'));

        // link the passed event if there was one to the series
        if ($request->eventLinkId) {
            if ($event = Event::find($request->eventLinkId)) {
                $event->series_id = $series->id;
                $event->save();
            }
        }

        // add to activity log
        Activity::log($series, $this->user, 1);

        // return response()->json($series);
        return response()->json(new SeriesResource($series));
    }

    public function export(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        RssFeed $feed
    ): View {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_series');
        $listParamSessionStore->setKeyPrefix('internal_series_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([SeriesController::class, 'index']));

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($this->baseQuery())
            ->setDefaultSort(['series.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $series = $query
            ->with('occurrenceType', 'visibility', 'tags')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('series.feed', compact('series'));
    }

    public function createOccurrence(Request $request): View
    {
        // create an event occurrence based on the series template

        $series = Series::find($request->id);

        $seriesOptions = ['' => ''] + Series::orderBy('name', 'ASC')->pluck('name', 'id')->all();
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

        return view('series.createOccurrence', compact('seriesOptions', 'userOptions', 'event'))
        ->with($this->getSeriesFormOptions())
        ->with(['series' => $series]);
    }

    public function update(Series $series, SeriesRequest $request): JsonResponse
    {
        $msg = '';

        $series->fill($request->input())->save();

        // TODO Revisit after auth is added
        // if (!$series->ownedBy($this->user)) {
        //     $this->unauthorized($request);
        // }

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

        $series->tags()->sync($syncArray);
        $series->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($series, $this->user, 2);

        // flash('Success', 'Your event template has been updated');

        //return redirect('series');
        return response()->json($series);
    }

    protected function unauthorized(SeriesRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Series $series): JsonResponse
    {
        // add to activity log
        Activity::log($series, $this->user, 3);

        $series->delete();

        return response()->json([], 204);
    }

    /**
     * Add a photo to a series.
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): void
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp',
        ]);

        $fileName = time().'_'.$request->file->getClientOriginalName();
        $filePath = $request->file('file')->storePubliclyAs('photos', $fileName, 'external');

        // attach to series
        if ($series = Series::find($id)) {
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

    protected function makePhoto(UploadedFile $file): ?Photo
    {
        return Photo::named($file->getClientOriginalName())
            ->makeThumbnail();
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

        if (!$series = Series::find($id)) {
            flash()->error('Error', 'No such series');

            return back();
        }

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = 'series';
        $follow->save();

        Log::info('User '.$id.' is following '.$series->name);

        // add to activity log
        Activity::log($series, $this->user, 6);

        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the series - '.$series->name,
                'Success' => view('series.single')
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

        // delete the follow
        $response = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'series')->first();
        $response->delete();

        // add to activity log
        Activity::log($series, $this->user, 7);

        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the series - '.$series->name,
                'Success' => view('series.single')
                    ->with(compact('series'))
                    ->render(),
            ];
        }

        flash()->success('Success', 'You are no longer following the series - '.$series->name);

        return back();
    }

    /**
     * Get the default filters array.
     *
     * @return array
     */
    public function getDefaultFilters()
    {
        return [];
    }
}
