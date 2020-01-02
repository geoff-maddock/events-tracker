<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Entity;
use App\Event;
use App\EventType;
use App\Follow;
use App\Http\Requests\SeriesRequest;
use App\OccurrenceDay;
use App\OccurrenceType;
use App\OccurrenceWeek;
use App\Photo;
use App\Series;
use App\Tag;
use App\User;
use App\Visibility;
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
    protected $prefix;
    protected $defaultRpp;
    protected $defaultSortBy;
    protected $defaultSortOrder;
    protected $childRpp;
    protected $rpp;
    protected $page;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $hasFilter;
    protected $filters;

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

        $this->defaultCriteria = null;
        $this->hasFilter = 0;
        parent::__construct();
    }

    /**
     * Update the page list parameters from the request.
     *
     * @param $filters
     */
    protected function getPaging($filters): void
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        $this->rpp = $filters['rpp'] ?? $this->rpp;
    }

    /**
     * Update the page list parameters from the request.
     */
    protected function updatePaging($request)
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        }

        // set sort direction
        if ($request->input('sort_order')) {
            $this->sortOrder = $request->input('sort_order');
        }

        // set results per page
        if ($request->input('rpp')) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Checks if there is a valid filter.
     *
     * @param $filters
     */
    public function hasFilter($filters): bool
    {
        $arr = $filters;
        unset($arr['rpp'], $arr['sortOrder'], $arr['sortBy'], $arr['page']);

        return count(array_filter($arr, function ($x) { return !empty($x); }));
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
        return $this->setAttribute('filters', $input, $request);
    }

    /**
     * Set user session attribute.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    public function setAttribute($attribute, $value, Request $request)
    {
        $request->session()->put($this->prefix.$attribute, $value);
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
     * @return string
     *
     * @throws \Throwable
     */
    public function index(Request $request)
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
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        $series = $this->series
            ->whereNotNull('cancelled_at')
            ->orderBy('occurrence_type_id', 'ASC')
            ->orderBy('occurrence_week_id', 'ASC')
            ->orderBy('occurrence_day_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $series = $series->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

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
     * @param $slug
     *
     * @return Response | View | string
     *
     * @throws \Throwable
     */
    public function indexRelatedTo($slug)
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
     * @param $tag
     *
     * @return Response | View | string
     *
     * @throws \Throwable
     */
    public function indexTags(Request $request, $tag)
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

    /**
     * Show a form to create a new series.
     *
     * @return View | string
     **/
    public function create()
    {
        // get a list of venues
        $venues = ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all();

        // get a list of promoters
        $promoters = ['' => ''] + Entity::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Promoter');
        })->orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $eventTypes = ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $occurrenceTypes = ['' => ''] + OccurrenceType::pluck('name', 'id')->all();
        $days = ['' => ''] + OccurrenceDay::pluck('name', 'id')->all();
        $weeks = ['' => ''] + OccurrenceWeek::pluck('name', 'id')->all();

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $userList = ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('series.create', compact('venues', 'eventTypes', 'visibilities', 'tags', 'entities', 'promoters', 'weeks', 'days', 'occurrenceTypes', 'userList'));
    }

    public function show(Series $series)
    {
        $events = $series->events()->paginate($this->childRpp);
        $threads = $series->threads()->paginate($this->childRpp);

        return view('series.show', compact('series', 'events', 'threads'));
    }

    /**
     * Stores a series entity.
     *
     * @return RedirectResponse
     */
    public function store(Request $request,
                          Series $series)
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

        flash()->success('Success', 'Your event series has been created');

        //return redirect('series');
        return redirect()->route('series.show', compact('series'));
    }

    public function edit(Series $series)
    {
        // get a list of venues
        $venues = ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all();

        // get a list of promoters
        $promoters = ['' => ''] + Entity::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Promoter');
        })->orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $eventTypes = ['' => ''] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $occurrenceTypes = ['' => ''] + OccurrenceType::pluck('name', 'id')->all();
        $days = ['' => ''] + OccurrenceDay::pluck('name', 'id')->all();
        $weeks = ['' => ''] + OccurrenceWeek::pluck('name', 'id')->all();

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $userList = User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('series.edit', compact('series', 'venues', 'eventTypes', 'visibilities', 'tags', 'entities', 'promoters', 'weeks', 'days', 'occurrenceTypes', 'userList'));
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
        $event = new \App\Event(['name' => $series->name,
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
     *
     * @param int $id
     *
     * @return void
     */
    public function addPhoto($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        // attach to series
        $series = Series::find($id);

        // make the photo object from the file in the request
        $photo = $this->makePhoto($request->file('file'));

        // count existing photos, and if zero, make this primary
        if (0 == count($series->photos)) {
            $photo->is_primary = 1;
        }

        $photo->save();

        // attach to series
        $series->addPhoto($photo);
    }

    protected function makePhoto(UploadedFile $file)
    {
        return Photo::named($file->getClientOriginalName())
            ->move($file);
    }

    /**
     * Delete a photo.
     *
     * @param int $id
     *
     * @return void
     */
    public function deletePhoto($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        // detach from event
        $series = Series::find($id);
        $series->removePhoto($photo);

        $photo = $this->deletePhoto($request->file('file'));
        $photo->save();
    }

    /**
     * Mark user as following the series.
     *
     * @param $id
     *
     * @return Response | RedirectResponse | array
     *
     * @throws \Throwable
     */
    public function follow($id, Request $request)
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
     * @param $id
     *
     * @return Response | RedirectResponse | array
     *
     * @throws \Throwable
     */
    public function unfollow($id, Request $request)
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
     * Builds the criteria from the session.
     *
     * @return $query
     */
    public function buildCriteria(Request $request)
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
            $query->where('name', 'like', '%'.$name.'%');
        }

        if (!empty($filters['filter_occurrence_type'])) {
            $type = $filters['filter_occurrence_type'];
            // add has clause
            $query->whereHas('occurrenceType',
                function ($q) use ($type) {
                    $q->where('name', '=', ucfirst($type));
                });
        }

        if (!empty($filters['filter_occurrence_week'])) {
            $week = $filters['filter_occurrence_week'];
            // add has clause
            $query->whereHas('occurrenceWeek',
                function ($q) use ($week) {
                    $q->where('name', '=', ucfirst($week));
                });
        }

        if (!empty($filters['filter_occurrence_day'])) {
            $day = $filters['filter_occurrence_day'];
            // add has clause
            $query->whereHas('occurrenceDay',
                function ($q) use ($day) {
                    $q->where('name', '=', ucfirst($day));
                });
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
            ->get($this->prefix.$attribute, $default);
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
