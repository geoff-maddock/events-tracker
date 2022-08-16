<?php

namespace App\Http\Controllers;

use App\Filters\ForumFilters;
use App\Http\Requests\ForumRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Forum;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View as ViewView;

class ForumsController extends Controller
{
    // define a list of variables
    protected int $rpp;

    protected int $page;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected array $defaultCriteria;

    protected bool $hasFilter;

    protected string $prefix;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected ForumFilters $filter;

    protected array $criteria;

    public function __construct(ForumFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // prefix for session storage
        $this->prefix = 'app.forums.';

        // default list variables
        $this->rpp = 10;
        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = [];
        $this->hasFilter = false;
        $this->filter = $filter;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum index');

            return redirect()->back();
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
            ->with('visibility')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $forums;
        }

        return view('forums.index')
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
                ->with(compact('forums'))
                ->render();
    }

    /**
     * Display a listing of the resource.
     */
    public function indexAll(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum index');

            return redirect()->back();
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
            ->with('visibility')
            ->paginate(1000000);

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $forums;
        }

        return view('forums.index')
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
                ->with(compact('forums'))
                ->render();
    }

    /**
     * Filter a list of forums.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum index');

            return redirect()->back();
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
            ->with('visibility')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $forums;
        }

        return view('forums.index')
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
                ->with(compact('forums'))
                ->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): ViewView
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $forum = new Forum();
        $forum->visibility_id = Visibility::VISIBILITY_PUBLIC;

        return view('forums.create', compact('forum'))->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ForumRequest $request, Forum $forum): RedirectResponse
    {
        $msg = '';

        // get the request
        $input = $request->all();

        $forum = $forum->create($input);

        // add to activity log
        Activity::log($forum, $this->user, 1);

        flash()->success('Success', 'Your forum has been created');

        return redirect()->route('forums.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(
        Forum $forum,
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): RedirectResponse | View {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()->where('forum_id', $forum->id)->orderBy('created_at', 'desc')
        ->select('threads.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['threads.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        /* @phpstan-ignore-next-line */
        $threads = $query->visible($this->user)
            ->with('visibility')
            ->paginate(10000000);

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        $threads = Thread::where('forum_id', $forum->id)->orderBy('created_at', 'desc')->paginate(1000000);
        $threads->filter(function ($e) {
            return ($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id);
        });

        // pass a slug for the forum
        $slug = $forum->description;

        return view('threads.index')
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
            ->with(compact('threads', 'slug'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Forum $forum): View
    {
        $this->middleware('auth');

        return view('forums.edit', compact('forum'))->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ForumRequest $request, Forum $forum): RedirectResponse
    {
        $msg = '';

        $forum->fill($request->input())->save();

        if (!$forum->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        // add to activity log
        Activity::log($forum, $this->user, 2);

        flash('Success', 'Your forum has been updated');

        return redirect('forums');
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
    public function destroy(Forum $forum): RedirectResponse
    {
        // add to activity log
        Activity::log($forum, $this->user, 3);

        $forum->delete();

        flash()->success('Success', 'Your forum has been deleted!');

        return redirect('forums');
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
     * @return RedirectResponse|View
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

    protected function getFilterOptions(): array
    {
        return [
            'userOptions' => ['' => '&nbsp;'] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'seriesOptions' => ['' => ''] + Series::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['forums.name' => 'Name', 'forums.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilities' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
        ];
    }
}
