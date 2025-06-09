<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\ActivityFilters;
use App\Http\Resources\ActivityCollection;
use App\Http\Requests\SeriesRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;

class ActivityController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $defaultSortCriteria;

    protected bool $hasFilter = false;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected ActivityFilters $filter;

    public function __construct(ActivityFilters $filter)
    {
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.activities.';

        // default list variables
        $this->defaultLimit = 100;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['object_name' => 'desc'];

        $this->limit = 100;
        $this->sort = 'object_name';
        $this->sortDirection = 'desc';

        parent::__construct();
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix('internal_activity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ActivityController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Activity::query()->select('activities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['activities.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the activities
        $activities = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('activities.index')
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
            ->with(compact('activities'))
            ->render();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix('internal_activity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ActivityController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Activity::query()->select('activities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['activities.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the activities
        $activities = $query->paginate($listResultSet->getLimit());

        return response()->json(new ActivityCollection($activities));
    }

    protected function unauthorized(SeriesRequest $request): Response | RedirectResponse
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect('activity');
    }

    /**
     * Get the default filters array.
     */
    public function getDefaultFilters(): array
    {
        return [];
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
        $keyPrefix = $request->get('key') ?? 'internal_activity_index';
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear sorting from session
        $listParamSessionStore->clearSort();

        return redirect()->route('activities.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_activity_index';
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'activities.index');
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['activities.object_name' => 'Name', 'activities.object_table' => 'Table', 'activities.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return  [
            'actionOptions' => ['' => '&nbsp;'] + Action::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'userOptions' => ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }
}
