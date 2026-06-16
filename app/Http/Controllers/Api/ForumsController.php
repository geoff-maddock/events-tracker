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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

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
            flash()->error('Unauthorized', 'Your cannot view the forum index');

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
     * Display a listing of the resource.
     */
    public function indexAll(
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
            ->paginate(1000000);

        // saves the updated session
        $listParamSessionStore->save();

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
        $forum = $forum->create($request->all());

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
        if (!$forum->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $input = $request->all();

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
        if (!$forum->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $input = $request->all();
        $scalarInput = array_intersect_key($input, array_flip($forum->getFillable()));
        if (!empty($scalarInput)) {
            $forum->fill($scalarInput)->save();
        }

        Activity::log($forum, $this->user, 2);

        return response()->json($forum);
    }

    protected function unauthorized(ForumRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Forum $forum): JsonResponse
    {
        // add to activity log
        Activity::log($forum, $this->user, 3);

        $forum->delete();

        return response()->json([], 204);
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
        $keyPrefix = $request->get('key') ?? 'internal_forum_index';
        $listParamSessionStore->setBaseIndex('internal_forum');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('forums.index');
    }

    /**
     * Reset the filtering of entities.
     *
     * @return RedirectResponse
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ) {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_forum_index';
        $listParamSessionStore->setBaseIndex('internal_forum');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'forums.index');
    }

    /**
     * Get the default filters array.
     */
    public function getDefaultFilters(): array
    {
        return [];
    }

}
