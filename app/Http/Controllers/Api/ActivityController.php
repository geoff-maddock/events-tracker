<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\ActivityFilters;
use App\Http\Resources\ActivityCollection;
use App\Http\Resources\ActivityResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultSortCriteria = ['created_at' => 'desc'];

        $this->limit = 100;
        $this->sort = 'created_at';
        $this->sortDirection = 'desc';

        parent::__construct();
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix('internal_activity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ActivityController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Activity::query()
            ->select('activities.*')
            ->with(['user.profile', 'action']);

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['activities.'.$this->defaultSort => $this->defaultSortDirection]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the activities
        $activities = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        return response()->json(new ActivityCollection($activities));
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix('internal_activity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ActivityController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Activity::query()
            ->select('activities.*')
            ->with(['user.profile', 'action']);

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

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): JsonResponse
    {
        return response()->json(new ActivityResource($activity));
    }

    public function destroy(Activity $activity): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $activity->delete();

        return response()->json([], 204);
    }

}
