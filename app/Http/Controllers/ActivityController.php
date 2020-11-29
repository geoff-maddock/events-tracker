<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Http\Requests\SeriesRequest;
use App\Series;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class ActivityController extends Controller
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
    protected string $entityType;

    public function __construct()
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
        return Activity::query();
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

        // get activities
        $activities = $query->paginate($this->rpp);

        return view('activity.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('activities'))
            ->render();
    }

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

        $activities = $query->paginate($this->rpp);

        return view('activity.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter, 'filters' => $this->filters])
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

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return redirect('activity');
    }

    /**
     * Get the current page for this module.
     *
     * @return int
     */
    public function getPage(Request $request)
    {
        return $this->getAttribute($request, 'page', 1);
    }

    /**
     * Get the current results per page.
     *
     * @return int
     */
    public function getRpp(Request $request)
    {
        return $this->getAttribute($request, 'rpp', $this->rpp);
    }

    /**
     * Get the sort order and column.
     *
     * @return array
     */
    public function getSort(Request $request)
    {
        return $this->getAttribute($request, 'sort', $this->getDefaultSort());
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
     *
     * @return $query
     */
    public function buildCriteria(Request $request)
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = $this->baseCriteria();

        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%'.$name.'%');
        }

        if (!empty($filters['filter_object_table'])) {
            $object_table = $filters['filter_object_table'];
            $query->where('object_table', 'like', '%'.$object_table.'%');
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
        return $this->getAttribute($request, 'filters', $this->getDefaultFilters());
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getAttribute(Request $request, $attribute, $default = null)
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
