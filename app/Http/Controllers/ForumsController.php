<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\ForumFilters;
use App\Http\Requests\ForumRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Forum;
use App\Models\Thread;
use App\Models\Visibility;
use App\Services\OptionLists;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View as ViewView;

class ForumsController extends Controller
{
    use BuildsListPages;

    // define a list of variables
    protected int $rpp;

    protected int $page;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected array $defaultCriteria;

    protected string $prefix;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected ForumFilters $filter;

    protected array $criteria;

    public function __construct(ForumFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

        // A forum is site structure, not user content, so only admins may add,
        // edit or remove one. Hiding the "Add Forum" button is not enough on
        // its own: the resource routes let any signed-in user POST /forums,
        // and destroy carried no auth middleware at all.
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (!$user || !$user->isAdmin()) {
                flash()->error('Unauthorized', 'You cannot manage forums');

                return redirect()->route('forums.index');
            }

            return $next($request);
        }, ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

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

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string|RedirectResponse {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum index');

            return redirect()->back();
        }

        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_forum',
            'internal_forum_index',
            action([ForumsController::class, 'index']),
            $this->filter,
            Forum::query()->select('forums.*'),
            ['forums.created_at' => 'desc'],
        );

        /* @phpstan-ignore-next-line */
        $forums = $listResultSet->getList()->visible($this->user)
            ->with(['visibility', 'threadsCount'])
            ->paginate($listResultSet->getLimit());

        return view('forums.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
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
    ): string|RedirectResponse {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $visibilities = ['' => ''] + OptionLists::visibilities();

        $forum = new Forum();
        $forum->visibility_id = Visibility::VISIBILITY_PUBLIC;

        return view('forums.create-tw', compact('forum'))->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ForumRequest $request, Forum $forum): RedirectResponse
    {
        $msg = '';

        // get the request

        $forum = $forum->create($request->validated());

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

        // the forum's threads, as the thread list
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_thread',
            'internal_thread_index',
            action([ThreadsController::class, 'index']),
            $this->filter,
            Thread::query()->where('forum_id', $forum->id)->orderBy('created_at', 'desc')->select('threads.*'),
            ['threads.created_at' => 'desc'],
        );

        /* @phpstan-ignore-next-line */
        $threads = $listResultSet->getList()->visible($this->user)
            ->with('visibility', 'entities', 'tags', 'event', 'series', 'user.photos', 'lastPost.user')
            ->withCount('posts')
            ->paginate($listResultSet->getLimit());

        // pass a slug for the forum
        $slug = $forum->description;

        return view('threads.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
            ->with(compact('threads', 'slug'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Forum $forum): View
    {
        return view('forums.edit-tw', compact('forum'))->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ForumRequest $request, Forum $forum): RedirectResponse
    {
        // ForumRequest::authorize() limits forum changes to admins; the ownership check
        // that used to follow the save never took effect, and admins edit each other's forums
        $forum->fill($request->validated())->save();

        // add to activity log
        Activity::log($forum, $this->user, 2);

        flash('Success', 'Your forum has been updated');

        return redirect('forums');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Forum $forum): RedirectResponse
    {
        if (!$forum->deleteIfEmpty()) {
            flash()->error('Not deleted', 'This forum still has threads. Move or delete them first.');

            return back();
        }

        // add to activity log
        Activity::log($forum, $this->user, 3);

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
        $this->clearList($listParamSessionStore, 'internal_forum', $request->get('key') ?? 'internal_forum_index', filters: false);

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
        $this->clearList($listParamSessionStore, 'internal_forum', $request->get('key') ?? 'internal_forum_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'forums.index'));
    }

    protected function getFilterOptions(): array
    {
        return [
            'userOptions' => ['' => '&nbsp;'] + OptionLists::users('name'),
            'tagOptions' => ['' => '&nbsp;'] + OptionLists::tags('slug'),
            'seriesOptions' => ['' => ''] + OptionLists::publicSeries(),
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
            'visibilities' => ['' => ''] + OptionLists::visibilities(),
        ];
    }
}
