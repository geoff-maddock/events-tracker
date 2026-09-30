<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\ForumFilters;
use App\Http\Requests\ForumPatchRequest;
use App\Http\Requests\ForumRequest;
use App\Http\Resources\ForumCollection;
use App\Http\Resources\ForumResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Forum;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ForumsController extends Controller
{
    // define a list of variables
    protected int $rpp;

    protected int $page;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected array $defaultCriteria;

    protected string $prefix;

    protected array $filters;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    // this is the class specifying the filters methods for each field
    protected ForumFilters $filter;

    protected array $criteria;

    public function __construct(ForumFilters $filter)
    {
        // $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // prefix for session storage
        $this->prefix = 'app.forums.';

        // default list variables
        $this->rpp = 10;
        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = [];
        $this->filter = $filter;

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultSortCriteria = ['forums.created_at' => 'desc'];

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_forum');
        $listParamSessionStore->setKeyPrefix('internal_forum_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ForumsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Forum::query()
            ->with(['visibility', 'threadsCount'])
            ->select('forums.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultLimit($this->defaultLimit)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the forums
        $forums = $query->paginate($listResultSet->getLimit());

        return response()->json(new ForumCollection($forums));
    }

    /**
     * Filter a list of forums.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_forum');
        $listParamSessionStore->setKeyPrefix('internal_forum_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ForumsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Forum::query()
        ->select('forums.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['forums.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        /* @phpstan-ignore-next-line */
        $forums = $query->visible($this->user)
            ->with(['visibility', 'threadsCount'])
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        return response()->json(new ForumCollection($forums));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ForumRequest $request, Forum $forum): JsonResponse
    {
        // forums are site structure: admin only, as on the web
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $forum = $forum->create($request->validated());

        // add to activity log
        Activity::log($forum, $this->user, 1);

        return response()->json($forum);
    }

    /**
     * Display the specified resource.
     */
    public function show(Forum $forum): JsonResponse
    {
        return response()->json(new ForumResource($forum));
    }

    /**
     * PUT: full replacement of the resource. Optional fillable scalars
     * omitted from the body are reset to null.
     */
    public function update(ForumRequest $request, Forum $forum): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();

        foreach (['description'] as $field) {
            if (!array_key_exists($field, $input)) {
                $input[$field] = null;
            }
        }

        $forum->fill($input)->save();

        Activity::log($forum, $this->user, 2);

        return response()->json($forum);
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched.
     */
    public function patch(ForumPatchRequest $request, Forum $forum): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();
        $scalarInput = array_intersect_key($input, array_flip($forum->getFillable()));
        if (!empty($scalarInput)) {
            $forum->fill($scalarInput)->save();
        }

        Activity::log($forum, $this->user, 2);

        return response()->json($forum);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Forum $forum): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        if (!$forum->deleteIfEmpty()) {
            return response()->json(['message' => 'This forum still has threads. Move or delete them first.'], 409);
        }

        // add to activity log
        Activity::log($forum, $this->user, 3);

        return response()->json([], 204);
    }

}
