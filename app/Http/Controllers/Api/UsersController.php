<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\UserFilters;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Filters\EventFilters;
use App\Mail\UserUpdate;
use App\Mail\WeeklyUpdate;
use App\Http\Resources\EventCollection;
use App\Models\Activity;
use App\Models\Profile;
use App\Models\User;
use App\Services\BestEffortMailer;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class UsersController extends Controller
{
    public const DEFAULT_SHOW_COUNT = 100;

    protected string $prefix;

    protected int $page;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    // array of sort criteria to be applied in order
    protected array $defaultSortCriteria;

    protected bool $hasFilter;

    protected array $filters;

    protected array $tabs;

    protected array $defaultTabs;

    protected UserFilters $filter;

    public function __construct(UserFilters $filter)
    {
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.users.';

        // default list variables - move to function that set from session or default
        $this->defaultSort = 'users.created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultLimit = 25;

        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['users.created_at' => 'desc'];

        // tabs
        $this->defaultTabs = ['events' => 'created', 'following' => 'tags'];

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ):  JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_user');
        $listParamSessionStore->setKeyPrefix('internal_user_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([UsersController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only user entities are returned
        $baseQuery = User::query()
        ->leftJoin('user_statuses', 'users.user_status_id', '=', 'user_statuses.id')
        ->select('users.*')
        ->addSelect(['last_active' => Activity::select('created_at')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
        ])
        ->withCasts(['last_active' => 'datetime']);

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the users
        $users = $query
            ->with(['profile', 'status', 'lastActivity', 'followedTags', 'followedEntities', 'followedSeries', 'followedThreads', 'photos'])
            ->paginate($listResultSet->getLimit());

        return response()->json(new UserCollection($users));
    }

    /**
     * Filter the list of users.
     *
     * @throws Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_user');
        $listParamSessionStore->setKeyPrefix('internal_user_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([UsersController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = User::query()
        ->leftJoin('user_statuses', 'users.user_status_id', '=', 'user_statuses.id')
        ->select('users.*')
        ->addSelect(['last_active' => Activity::select('created_at')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
        ])
        ->withCasts(['last_active' => 'created_at']);

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['users.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the users
        $users = $query
            ->with(['profile', 'status', 'lastActivity', 'followedTags', 'followedEntities', 'followedSeries', 'followedThreads', 'photos'])
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        return response()->json(new UserCollection($users));
    }

    /**
     * Get the default tags array.
     */
    public function getDefaultTabs(): array
    {
        return ['events' => 'created', 'following' => 'tags'];
    }

    public function show(User $user, Request $request): JsonResponse
    {
        return response()->json(new UserResource($user));
    }

    public function store(UserRequest $request, User $user): JsonResponse
    {   
        // user_status_id is fillable, so only take the account fields here
        $user = User::create($request->only(['name', 'email', 'password']));

        // set the user status
        $user->user_status_id = 1;
        $user->save();

        // if there is no profile, create one
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();

        // add to activity log
        Activity::log($user, $this->user, 1);

        return response()->json(new UserResource($user));
    }


    public function update(User $user, Request $request): JsonResponse
    {
        // same rule as destroy: self, or a user with grant_access
        if (!$this->user || ($this->user->id !== $user->id && !$this->user->can('grant_access'))) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $isAdmin = $this->user->can('grant_access');

        $request->validate([
            'name' => ['sometimes', 'required', 'min:6', 'max:255', 'regex:/^[a-zA-Z0-9\s._-]+$/'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'required', 'min:8', 'max:60'],
            'profile' => ['sometimes', 'array'],
            'group_list' => ['sometimes', 'array'],
            'group_list.*' => ['integer', 'exists:groups,id'],
        ]);

        // status and group membership are admin-only fields
        $userFields = $request->only($isAdmin
            ? ['name', 'slug', 'email', 'password', 'user_status_id']
            : ['name', 'slug', 'email', 'password']);

        $profileFields = $request->input('profile', []);

        $user->fill($userFields)->save();

        // Some legacy users have no profile row yet (EVENTREPO-VW); create one on demand.
        $user->profile()->firstOrCreate([])->fill($profileFields)->save();

        if ($isAdmin && $request->has('group_list')) {
            $user->groups()->sync($request->input('group_list', []));
        }

        // add to activity log
        Activity::log($user, $this->user, 2);

        return response()->json(new UserResource($user));
    }

    /**
     * @throws Exception
     */
    public function destroy(User $user): JsonResponse
    {
        // same rule as the web UsersController::authorizeUserChange
        if (!$this->user || ($this->user->id !== $user->id && !$this->user->can('grant_access'))) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        // add to activity log
        Activity::log($user, $this->user, 3);

        $user->delete();

        return response()->json([], 204);
    }


    public function eventsAttending(
        User $user,
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        EventFilters $eventFilters
    ): JsonResponse {
        // initialize the list parameter session store for this route
        $listParamSessionStore->setBaseIndex('api_user_event');
        $listParamSessionStore->setKeyPrefix('api_user_event_index');

        // base query of events the user is attending
        $baseQuery = $user->getAttending()
            ->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')
            ->leftJoin('entities as venue', 'events.venue_id', '=', 'venue.id')
            ->leftJoin('entities as promoter', 'events.promoter_id', '=', 'promoter.id')
            ->select('events.*');

        $listEntityResultBuilder
            ->setFilter($eventFilters)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'asc']);

        // build the result set with filters, sorting and limit
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();
        $query = $listResultSet->getList();

        $events = $query->with([
                'visibility',
                'venue',
                'eventStatus',
                'eventType',
                'promoter',
                'series',
                'tags',
                'entities',
            ])
            ->paginate($listResultSet->getLimit());

        return response()->json(new EventCollection($events));
    }
}
