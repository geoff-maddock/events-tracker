<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\ThreadFilters;
use App\Http\Requests\ThreadRequest;
use App\Http\Resources\ThreadCollection;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Thread;
use App\Models\Series;
use App\Models\Tag;
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

class ThreadsController extends Controller
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

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    // this is the class specifying the filters methods for each field
    protected ThreadFilters $filter;

    protected array $criteria;

    public function __construct(ThreadFilters $filter)
    {
        // prefix for session storage
        $this->prefix = 'app.threads.';

        // default list variables
        $this->rpp = 10;
        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = [];
        $this->hasFilter = false;
        $this->filter = $filter;

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultSortCriteria = ['threads.created_at' => 'desc'];

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
        // if the gate does not allow this user to show a thread redirect to home
        if (Gate::denies('show_thread')) {
            flash()->error('Unauthorized', 'Your cannot view the thread index');

            return redirect()->back();
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
        ->select('threads.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultLimit($this->defaultLimit)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $threads = $query->paginate($listResultSet->getLimit());

        return response()->json(new ThreadCollection($threads));
    }

    /**
     * Display a listing of the resource.
     */
    public function indexAll(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // if the gate does not allow this user to show a thread redirect to home
        if (Gate::denies('show_thread')) {
            flash()->error('Unauthorized', 'Your cannot view the thread index');

            return redirect()->back();
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
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
            ->paginate(1000000);

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $threads;
        }

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
                ->with(compact('threads'))
                ->render();
    }

    /**
     * Filter a list of threads.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // if the gate does not allow this user to show a thread redirect to home
        if (Gate::denies('show_thread')) {
            flash()->error('Unauthorized', 'Your cannot view the thread index');

            return redirect()->back();
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
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
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $threads;
        }

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
                ->with(compact('threads'))
                ->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): ViewView
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $thread = new Thread();
        $thread->visibility_id = Visibility::VISIBILITY_PUBLIC;

        return view('threads.create', compact('thread'))->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ThreadRequest $request, Thread $thread): RedirectResponse
    {
        $msg = '';

        // get the request
        $input = $request->all();

        $thread = $thread->create($input);

        // add to activity log
        Activity::log($thread, $this->user, 1);

        flash()->success('Success', 'Your thread has been created');

        return redirect()->route('threads.index');
    }

    // /**
    //  * Display the specified resource.
    //  */
    // public function show(
    //     Thread $thread,
    //     Request $request,
    //     ListParameterSessionStore $listParamSessionStore,
    //     ListEntityResultBuilder $listEntityResultBuilder
    // ): RedirectResponse | View {
    //     // if the gate does not allow this user to show a thread redirect to home
    //     if (Gate::denies('show_thread')) {
    //         flash()->error('Unauthorized', 'Your cannot view the thread');

    //         return redirect()->back();
    //     }

    //     // initialized listParamSessionStore with base index key
    //     $listParamSessionStore->setBaseIndex('internal_thread');
    //     $listParamSessionStore->setKeyPrefix('internal_thread_index');

    //     // set the index tab in the session
    //     $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

    //     // create the base query including any required joins; needs select to make sure only event entities are returned
    //     $baseQuery = Thread::query()->where('thread_id', $thread->id)->orderBy('created_at', 'desc')
    //     ->select('threads.*');

    //     $listEntityResultBuilder
    //         ->setFilter($this->filter)
    //         ->setQueryBuilder($baseQuery)
    //         ->setDefaultSort(['threads.created_at' => 'desc']);

    //     // get the result set from the builder
    //     $listResultSet = $listEntityResultBuilder->listResultSetFactory();

    //     // get the query builder
    //     $query = $listResultSet->getList();

    //     /* @phpstan-ignore-next-line */
    //     $threads = $query->visible($this->user)
    //         ->with('visibility')
    //         ->paginate(10000000);

    //     // saves the updated session
    //     $listParamSessionStore->save();

    //     $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

    //     $threads = Thread::whereRelation('visibility','name','Public')->where('thread_id', $thread->id)->orderBy('created_at', 'desc')->paginate(1000000);

    //     // pass a slug for the thread
    //     $slug = $thread->description;

    //     return view('threads.index')
    //         ->with(array_merge(
    //             [
    //                 'limit' => $listResultSet->getLimit(),
    //                 'sort' => $listResultSet->getSort(),
    //                 'direction' => $listResultSet->getSortDirection(),
    //                 'hasFilter' => $this->hasFilter,
    //                 'filters' => $listResultSet->getFilters(),
    //             ],
    //             $this->getFilterOptions(),
    //             $this->getListControlOptions()
    //         ))
    //         ->with(compact('threads', 'slug'));
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Thread $thread): View
    {
        $this->middleware('auth');

        return view('threads.edit', compact('thread'))->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ThreadRequest $request, Thread $thread): RedirectResponse
    {
        $msg = '';

        $thread->fill($request->input())->save();

        if (!$thread->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        // add to activity log
        Activity::log($thread, $this->user, 2);

        flash('Success', 'Your thread has been updated');

        return redirect('threads');
    }

    protected function unauthorized(ThreadRequest $request): RedirectResponse | Response
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
    public function destroy(Thread $thread): RedirectResponse
    {
        // add to activity log
        Activity::log($thread, $this->user, 3);

        $thread->delete();

        flash()->success('Success', 'Your thread has been deleted!');

        return redirect('threads');
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
        $keyPrefix = $request->get('key') ?? 'internal_thread_index';
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('threads.index');
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
        $keyPrefix = $request->get('key') ?? 'internal_thread_index';
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'threads.index');
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
            'sortOptions' => ['threads.name' => 'Name', 'threads.created_at' => 'Created At'],
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
