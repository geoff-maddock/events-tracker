<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Follow;
use App\Http\Requests\SeriesRequest;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Photo;
use App\Models\Series;
use App\Services\RssFeed;
use App\Models\Tag;
use App\Models\TagType;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SeriesController extends Controller
{
    protected string $prefix;

    protected int $defaultRpp;

    protected string $defaultSortBy;

    protected string $defaultSortOrder;

    protected int $childRpp;

    protected int $rpp;

    protected int $page;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected array $defaultCriteria;

    protected bool $hasFilter;

    protected array $filters;

    protected Series $series;

    public function __construct(Series $series)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->series = $series;

        // prefix for session storage
        $this->prefix = 'app.series.';

        // default list variables
        $this->rpp = 100;
        $this->childRpp = 10;
        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->sortBy = 'name';
        $this->sortOrder = 'asc';

        $this->defaultRpp = 5;
        $this->defaultSortBy = 'name';
        $this->defaultSortOrder = 'asc';
        $this->filters = [];

        $this->defaultCriteria = [];
        $this->hasFilter = 0;
        parent::__construct();
    }

    /**
     * Update the page list parameters from the request.
     *
     * @param array $filters
     */
    protected function getPaging(array $filters): void
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        if (isset($filters['rpp']) && is_numeric($filters['rpp'])) {
            $this->rpp = $filters['rpp'];
        } else {
            $this->rpp = $this->defaultRpp;
        }
    }

    /**
     * Checks if there is a valid filter.
     *
     * @param array $filters
     */
    public function hasFilter(array $filters): bool
    {
        if (!is_array($filters)) {
            return false;
        }
        unset($filters['rpp'], $filters['sortOrder'], $filters['sortBy'], $filters['page']);

        return count(array_filter($filters, function ($x) { return !empty($x); }));
    }

    /**
     * Filter the list of events.
     *
     * @return View | string
     *
     * @internal param $Request
     *
     * @throws \Throwable
     */
    public function filter(Request $request)
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($this->filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        // get the criteria given the request (could pass filters instead?)
        $query = $this->buildCriteria($request);

        // apply the filters to the query
        // get the entities and paginate
        $series = $query->paginate($this->rpp);
        $series->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        return view('series.index')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'filters' => $this->filters,
                'hasFilter' => $this->hasFilter,
            ])
            ->with(compact('series'))
            ->render();
    }

    /**
     * Update the page list parameters from the request.
     *
     * @param Request $request
     */
    protected function updatePaging(Request $request)
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        }

        // set sort direction
        if ($request->input('sort_direction')) {
            $this->sortOrder = $request->input('sort_direction');
        }

        if (!empty($request->input('rpp')) && is_numeric($request->input('rpp'))) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Get the base criteria.
     */
    protected function baseCriteria()
    {
        $query = Series::where('cancelled_at', null)
            ->orderBy('occurrence_type_id', 'ASC')
            ->orderBy('occurrence_week_id', 'ASC')
            ->orderBy('occurrence_day_id', 'ASC')
            ->orderBy('name', 'ASC');

        return $query;
    }

    /**
     * Set filters attribute.
     *
     * @return array
     */
    public function setFilters(Request $request, array $input)
    {
        return $this->setAttribute($request, 'filters', $input);
    }

    /**
     * Set user session attribute.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    public function setAttribute(Request $request, $attribute, $value)
    {
        $request->session()->put($this->prefix . $attribute, $value);
    }

    /**
     * Reset the filtering of entities.
     *
     * @return Response | View | string
     *
     * @throws \Throwable
     */
    public function reset(Request $request)
    {
        // doesn't have filter, but temp
        $this->hasFilter = 0;

        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        // base criteria
        $query = $this->baseCriteria();

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get future events
        $series = $query->paginate($this->rpp);
        $series->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('series'))
            ->render();
    }

    /**
     * Reset the rpp, sort, order
     *
     *
     * @throws \Throwable
     */
    public function rppReset(Request $request): RedirectResponse
    {
        // set the rpp, sort, direction to default values
        $this->setFilters($request, array_merge($this->getFilters($request), $this->getDefaultRppFilters()));

        return redirect()->route('series.index');
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    public function index(Request $request)
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($filters);

        // base criteria
        $query = $this->buildCriteria($request);

        $series = $query->with('occurrenceType', 'visibility', 'tags')->paginate($this->rpp);

        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter, 'filters' => $this->filters])
            ->with(compact('series'))
            ->render();
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    public function indexCancelled(Request $request)
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($this->filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        $series = $this->series
            ->whereNotNull('cancelled_at')
            ->orderBy('occurrence_type_id', 'ASC')
            ->orderBy('occurrence_week_id', 'ASC')
            ->orderBy('occurrence_day_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate();

        //      $series = $series->filter(function ($e) {
        //          return ('Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        //       });

        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('series'))
            ->render();
    }

    /**
     * Display a listing of event series in a week view.
     *
     * @return View | string
     *
     * @throws \Throwable
     */
    public function indexWeek(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        $this->rpp = 5;

        // this is more complex because we want to show weeklies that fall on the days, plus monthlies that fall on the days
        // may be an iterative process that is called from the template to the series model that checks against each criteria and builds a list that way
        $series = Series::future()->get();

        return view('series.indexWeek')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('series'))
            ->render();
    }

    /**
     * Display a listing of series related to entity.
     *
     * @param string $slug
     *
     * @return Response | View | string
     *
     * @throws \Throwable
     */
    public function indexRelatedTo(string $slug)
    {
        $hasFilter = 1;
        $slug = urldecode($slug);

        $series = Series::getByEntity(strtolower($slug))
            ->where(function ($query) {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate();

        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('series', 'slug'))
            ->render();
    }

    /**
     * Display a listing of events by tag.
     *
     * @return Response | View | string
     *
     * @throws \Throwable
     */
    public function indexTags(Request $request, string $tag)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);
        $tag = urldecode($tag);

        $series = Series::getByTag(ucfirst($tag))
            ->where(function ($query) {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate();

        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter, 'filters' => $filters])
            ->with(compact('series', 'tag'))
            ->render();
    }

    protected function getSeriesFormOptions()
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
            'weekOptions' => ['' => ''] + OccurrenceWeek::pluck('name', 'id')->all()
        ];
    }

    /**
     * Show a form to create a new series.
     *
     * @return View | string
     **/
    public function create()
    {
        $userList = ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('series.create', compact('userList'))
        ->with($this->getSeriesFormOptions());
    }

    public function show(Series $series)
    {
        $events = $series->events()->paginate($this->childRpp);
        $threads = $series->threads()->paginate($this->childRpp);

        return view('series.show', compact('series', 'events', 'threads'));
    }

    public function store(SeriesRequest $request, Series $series)
    {
        $msg = '';
        $input = $request->all();

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!DB::table('tags')->where('id', $tag)->get()) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tagType()->associate(TagType::find(1));
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);
                $syncArray[] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
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

        flash()->success('Success', 'Your event series has been created');

        //return redirect('series');
        return redirect()->route('series.show', compact('series'));
    }

    public function edit(Series $series)
    {
        $userList = User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('series.edit', compact('series', 'userList'))
            ->with($this->getSeriesFormOptions());
    }

    public function export(
        Request $request,
        RssFeed $feed
    ) {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($filters);

        // base criteria
        $series = $this->buildCriteria($request)->take($this->rpp)->get();

        return view('series.feed', compact('series'));
    }

    public function createOccurrence(Request $request)
    {
        // create an event occurrence based on the series template

        $series = Series::find($request->id);

        // get a list of venues
        $venues = ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all();

        // get a list of promoters
        $promoters = ['' => ''] + Entity::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Promoter');
        })->orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $eventTypes = ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $seriesList = ['' => ''] + Series::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();

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

        return view('series.createOccurrence', compact('seriesList', 'event', 'venues', 'eventTypes', 'visibilities', 'tags', 'entities', 'promoters'))->with(['series' => $series]);
    }

    public function update(Series $series, SeriesRequest $request)
    {
        $msg = '';

        $series->fill($request->input())->save();

        if (!$series->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tagType()->associate(TagType::find(1));
                $newTag->save();
                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $series->tags()->sync($syncArray);
        $series->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($series, $this->user, 2);

        flash('Success', 'Your event template has been updated');

        //return redirect('series');
        return redirect()->route('series.show', compact('series'));
    }

    protected function unauthorized(SeriesRequest $request)
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Series $series)
    {
        // add to activity log
        Activity::log($series, $this->user, 3);

        $series->delete();

        return redirect('series');
    }

    /**
     * Add a photo to a series.
     */
    public function addPhoto(int $id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        $fileName = time() . '_' . $request->file->getClientOriginalName();
        $filePath = $request->file('file')->storeAs('photos', $fileName, 'public');

        // attach to series
        if ($series = Series::find($id)) {
            // make the photo object from the file in the request
            $photo = $this->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if ($series->photos && 0 === count($series->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to series
            $series->addPhoto($photo);
        }
    }

    protected function makePhoto(UploadedFile $file)
    {
        return Photo::named($file->getClientOriginalName())
            ->makeThumbnail();
    }

    /**
     * Mark user as following the series.
     * @return Response | RedirectResponse | array
     *
     * @throws \Throwable
     */
    public function follow(int $id, Request $request)
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

        Log::info('User ' . $id . ' is following ' . $series->name);

        // add to activity log
        Activity::log($series, $this->user, 6);

        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the series - ' . $series->name,
                'Success' => view('series.single')
                    ->with(compact('series'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are now following the series - ' . $series->name);

        return back();
    }

    /**
     * Mark user as unfollowing the series.
     *
     * @return Response | RedirectResponse | array
     *
     * @throws \Throwable
     */
    public function unfollow(int $id, Request $request)
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
                'Message' => 'You are no longer following the series - ' . $series->name,
                'Success' => view('series.single')
                    ->with(compact('series'))
                    ->render(),
            ];
        }

        flash()->success('Success', 'You are no longer following the series - ' . $series->name);

        return back();
    }

    /**
     * Set page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    public function setPage(Request $request, $input)
    {
        return $this->setAttribute($request, 'page', $input);
    }

    /**
     * Set results per page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    public function setRpp(Request $request, $input)
    {
        return $this->setAttribute($request, 'rpp', 5);
    }

    /**
     * Set sort order attribute.
     *
     * @return array
     */
    public function setSort(Request $request, array $input)
    {
        return $this->setAttribute($request, 'sort', $input);
    }

    /**
     * Builds the criteria from the session.
     */
    public function buildCriteria(Request $request): Builder
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = $this->baseCriteria();

        // add the criteria from the session
        // check request for passed filter values

        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%' . $name . '%');
        }

        if (!empty($filters['filter_occurrence_type'])) {
            $type = $filters['filter_occurrence_type'];
            // add has clause
            $query->whereHas(
                'occurrenceType',
                function ($q) use ($type) {
                    $q->where('name', '=', ucfirst($type));
                }
            );
        }

        if (!empty($filters['filter_occurrence_week'])) {
            $week = $filters['filter_occurrence_week'];
            // add has clause
            $query->whereHas(
                'occurrenceWeek',
                function ($q) use ($week) {
                    $q->where('name', '=', ucfirst($week));
                }
            );
        }

        if (!empty($filters['filter_occurrence_day'])) {
            $day = $filters['filter_occurrence_day'];
            // add has clause
            $query->whereHas(
                'occurrenceDay',
                function ($q) use ($day) {
                    $q->where('name', '=', ucfirst($day));
                }
            );
        }

        if (!empty($filters['filter_tag'])) {
            $tag = $filters['filter_tag'];
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });
        }

        // change this - should be separate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

    /**
     * Get session filters.
     *
     * @return array
     */
    public function getFilters(Request $request)
    {
        return $this->getAttribute('filters', $this->getDefaultFilters(), $request);
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getAttribute($attribute, $default = null, Request $request)
    {
        return $request->session()
            ->get($this->prefix . $attribute, $default);
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

    protected function getDefaultRppFilters(): array
    {
        return [
            'rpp' => $this->defaultRpp,
            'sortBy' => $this->defaultSortBy,
            'sortOrder' => $this->defaultSortOrder
        ];
    }

    /**
     * Returns true if the user has any filters outside of the default.
     *
     * @return bool
     */
    protected function getIsFiltered(Request $request)
    {
        if (($filters = $this->getFilters($request)) == $this->getDefaultFilters()) {
            return false;
        }

        return (bool) count($filters);
    }
}
