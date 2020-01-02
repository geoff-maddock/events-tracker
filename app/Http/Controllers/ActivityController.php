<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Follow;
use App\Http\Requests\SeriesRequest;
use App\Series;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Log;

class ActivityController extends Controller
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
    protected $entityType;

    public function __construct(Series $series)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        $this->entityType = 'activities';

        // prefix for session storage
        $this->prefix = 'app.activities.';

        // default list variables
        $this->defaultRpp = 100;
        $this->defaultSortBy = 'name';
        $this->defaultSortOrder = 'asc';

        $this->rpp = 100;
        $this->childRpp = 10;
        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->sortBy = 'name';
        $this->sortOrder = 'asc';
        $this->defaultCriteria = null;
        $this->hasFilter = 0;
        parent::__construct();
    }

    /**
     * Filter the list of activities.
     *
     * @return View | string
     *
     * @internal param $Request
     *
     * @throws \Throwable
     */
    public function filter(Request $request)
    {
        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // update filters based on the request input
        $this->setFilters($request, array_merge($this->getFilters($request), $request->input()));

        // get the merged filters
        $this->filters = $this->getFilters($request);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // flag that there are filters
        $this->hasFilter = count($this->filters);

        // get the criteria given the request (could pass filters instead?)
        $query = $this->buildCriteria($request);

        // apply the filters to the query
        // get the entities and paginate
        $activities = $query->paginate($this->rpp);
        $activities->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        return view('activity.index')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'filters' => $this->filters,
                'hasFilter' => $this->hasFilter,
            ])
            ->with(compact('activities'))
            ->render();
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
     *
     * @param $request
     */
    protected function updatePaging($request)
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        }

        // set sort direction
        if ($request->input('sort_direction')) {
            $this->sortOrder = $request->input('sort_direction');
        }

        // set results per page
        if ($request->input('rpp')) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Get the base criteria.
     */
    protected function baseCriteria()
    {
        $query = Activity::query();

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
        return $request->session()
            ->put($this->prefix.$attribute, $value);
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
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('series'))
            ->render();
    }

    public function index(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        // base criteria
        $query = $this->buildCriteria($request);

        $activities = $query->paginate($this->rpp);

        return view('activity.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter, 'filters' => $filters])
            ->with(compact('activities'))
            ->render();
    }

    public function show(Series $series)
    {
        $events = $series->events()->paginate($this->childRpp);
        $threads = $series->threads()->paginate($this->childRpp);

        return view('series.show', compact('series', 'events', 'threads'));
    }

    protected function unauthorized(SeriesRequest $request)
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Series $series)
    {
        $series->delete();

        return redirect('series');
    }

    /**
     * Mark user as following the series.
     *
     * @param $id
     *
     * @return Response
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

        flash()->success('Success', 'You are now following the series - '.$series->name);

        return back();
    }

    /**
     * Mark user as unfollowing the series.
     *
     * @param $id
     *
     * @return Response
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

        flash()->success('Success', 'You are no longer following the series.');

        return back();
    }

    /**
     * Criteria provides a way to define criteria to be applied to a tab on the index page.
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Get the current page for this module.
     *
     * @return integner
     */
    public function getPage()
    {
        return $this->getAttribute('page', 1);
    }

    /**
     * Get the current results per page.
     *
     * @return int
     */
    public function getRpp(Request $request)
    {
        return $this->getAttribute('rpp', $this->rpp);
    }

    /**
     * Get the sort order and column.
     *
     * @return array
     */
    public function getSort(Request $request)
    {
        return $this->getAttribute('sort', $this->getDefaultSort());
    }

    /**
     * Get the default sort array.
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return ['id', 'desc'];
    }

    /**
     * Set criteria.
     *
     * @param array $input
     *
     * @return string
     */
    public function setCriteria($input)
    {
        $this->criteria = $input;

        return $this->criteria;
    }

    /**
     * Set page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    public function setPage($input)
    {
        return $this->setAttribute('page', $input);
    }

    /**
     * Set results per page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    public function setRpp($input)
    {
        return $this->setAttribute('rpp', 5);
    }

    /**
     * Set sort order attribute.
     *
     * @return array
     */
    public function setSort(array $input)
    {
        return $this->setAttribute('sort', $input);
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

        // change this - should be separate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

    /**
     * Get session filters.
     */
    public function getFilters(Request $request): array
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
