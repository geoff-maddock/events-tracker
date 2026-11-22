<?php

namespace App\Http\Controllers;

use App\Filters\ThreadFilters;
use App\Http\Controllers\Concerns\BuildsListPages;
use App\Jobs\NotifyFollowers;
use App\Http\Requests\ThreadRequest;
use App\Http\Response\ResultSet\ListResultSet;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Follow;
use App\Models\Forum;
use App\Models\Like;
use App\Models\Series;
use App\Models\Tag;
use App\Models\TagType;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\User;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ThreadsController extends Controller
{
    use BuildsListPages;

    // define a list of variables
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $defaultSortCriteria;

    protected int $page;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected ThreadFilters $filter;

    protected array $criteria;

    public function __construct(ThreadFilters $filter)
    {
        $this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);
        // locking changes a thread for everyone, so it needs a signed-in user who may edit it
        $this->middleware('auth', ['only' => ['lock', 'unlock']]);

        // prefix for session storage
        $this->prefix = 'app.threads.';

        // default list variables - move to function that set from session or default
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultLimit = 10;

        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['thread.updated_at' => 'desc'];

        $this->page = 1;

        $this->filter = $filter;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startThreadList($listParamSessionStore, $listEntityResultBuilder, 'internal_thread_index');

        return $this->threadListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * Start a thread list page (#2406): every thread list shares one session
     * base index, the thread filters, the default sort and the sort columns
     * the list controls offer.
     *
     * @param array<string, mixed> $parentFilter
     */
    private function startThreadList(
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $keyPrefix,
        ?Builder $baseQuery = null,
        array $parentFilter = [],
    ): ListResultSet {
        return $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_thread',
            $keyPrefix,
            action([ThreadsController::class, 'index']),
            $this->filter,
            $baseQuery ?? $this->baseQuery(),
            ['threads.created_at' => 'desc'],
            parentFilter: $parentFilter,
            allowedSortFields: array_keys($this->getListControlOptions()['sortOptions']),
        );
    }

    /**
     * Threads with their author joined in, because every list offers a sort
     * by users.name; without the join that sort is an unknown-column error
     * (EVENTREPO-W2).
     */
    private function baseQuery(): Builder
    {
        return Thread::query()
            ->leftJoin('users', 'threads.created_by', '=', 'users.id')
            ->select('threads.*');
    }

    /**
     * Render the thread list view for a started list.
     *
     * @param array<string, mixed> $extra more view data (the page's tag, type, ...)
     *
     * @throws \Throwable
     */
    private function threadListPage(
        ListParameterSessionStore $listParamSessionStore,
        ListResultSet $listResultSet,
        array $extra = [],
    ): string {
        /* @phpstan-ignore-next-line */
        $threads = $listResultSet->getList()->visible($this->user)
            ->with('visibility', 'entities', 'tags', 'event', 'series', 'user.photos', 'lastPost.user')
            ->withCount('posts')
            ->paginate($listResultSet->getLimit());

        return view('threads.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet, extra: $extra))
            ->with(compact('threads'))
            ->render();
    }

    /**
     * Display a listing of the resource that the user is following.
     *
     * @throws \Throwable
     */
    public function indexFollowing(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $baseQuery = Thread::join('follows', 'threads.id', '=', 'follows.object_id')
            ->leftJoin('users', 'threads.created_by', '=', 'users.id')
            ->where('follows.object_type', '=', 'thread')
            ->where('follows.user_id', '=', $this->user->id)
            ->orderBy('follows.created_at', 'desc')
            ->select('threads.*');

        $listResultSet = $this->startThreadList($listParamSessionStore, $listEntityResultBuilder, 'internal_thread_index', $baseQuery);

        return $this->threadListPage($listParamSessionStore, $listResultSet, ['type' => 'Followed Threads']);
    }

    /**
     * Filter the list of threads.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startThreadList($listParamSessionStore, $listEntityResultBuilder, 'internal_thread_index');

        return $this->threadListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * Reset the limit, sort, order.
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_thread', $request->get('key') ?? 'internal_thread_index', filters: false);

        return redirect()->route('threads.index');
    }

    /**
     * Reset the filtering of threads.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_thread', $request->get('key') ?? 'internal_thread_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'threads.index'));
    }

    /**
     * Display a listing of the resource.
     *
     * @throws \Throwable
     */
    public function indexAll(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startThreadList($listParamSessionStore, $listEntityResultBuilder, 'internal_thread_index');

        return $this->threadListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * Display a listing of threads by tag.
     *
     * @throws \Throwable
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $tag
    ): string {
        $tag = urldecode($tag);

        $listResultSet = $this->startThreadList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_thread_tags',
            parentFilter: ['tag' => ucfirst($tag)],
        );

        return $this->threadListPage($listParamSessionStore, $listResultSet);
    }

    /**
     * Display a listing of threads by category.
     *
     * @throws \Throwable
     */
    public function indexCategories(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $category
    ): string {
        $listResultSet = $this->startThreadList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_thread_category',
            parentFilter: ['category' => strtolower($category)],
        );

        return $this->threadListPage($listParamSessionStore, $listResultSet, ['tag' => $category]);
    }

    /**
     * Display a listing of threads by series.
     *
     * @throws \Throwable
     */
    public function indexSeries(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $series
    ): string {
        $series = urldecode($series);

        $listResultSet = $this->startThreadList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_thread_series',
            parentFilter: ['series' => ucfirst($series)],
        );

        return $this->threadListPage($listParamSessionStore, $listResultSet, ['tag' => $series]);
    }

    /**
     * Display a listing of threads by entity.
     *
     * @throws \Throwable
     */
    public function indexRelatedTo(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $relatedTo
    ): string {
        $relatedTo = Str::title(str_replace('-', ' ', $relatedTo));

        $listResultSet = $this->startThreadList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_thread_related_to',
            parentFilter: ['related' => $relatedTo],
        );

        return $this->threadListPage($listParamSessionStore, $listResultSet, ['tag' => $relatedTo]);
    }

    public function create(): View
    {
        return view('threads.create')->with($this->getFormOptions());
    }

    protected function getFormOptions(): array
    {
        return [
            'threadCategoryOptions' => ['' => ''] + ThreadCategory::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'tagOptions' => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityOptions' => Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'forumOptions' => ['' => ''] + Forum::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ThreadRequest $request): RedirectResponse
    {
        $msg = '';

        $input = $request->validated();

        $tagArray = $request->input('tag_list', []);
        $tags = Tag::resolveList($tagArray, $this->user);
        $syncArray = $tags->modelKeys();
        foreach ($tags as $tag) {
            $msg .= $tag->wasRecentlyCreated ? ' Added tag '.$tag->name.'.' : ' Linked tag '.$tag->name.'.';
        }

        $thread = Thread::create($input);

        $thread->tags()->attach($syncArray);
        $thread->entities()->attach($request->input('entity_list'));
        $thread->series()->attach($request->input('series_list'));

        // here, make a call to notify all users who are following any of the sync'd tags
        NotifyFollowers::dispatch($thread);

        // add to activity log
        Activity::log($thread, $this->user, 1);

        flash()->success('Success', 'Your thread has been created. '.$msg);

        return redirect()->route('threads.show', compact('thread'));
    }

    public function show(Thread $thread): RedirectResponse | View
    {
        // the same rule as the thread lists, so an author can open their own private
        // thread and a guest can't open anyone's (#2342)
        abort_unless($thread->isVisibleTo($this->user), 404);

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $thread->load(['user', 'entities', 'tags', 'series', 'event', 'posts.user', 'posts.entities', 'posts.tags']);

        // Pre-compute follow/like state for the current user to avoid per-view queries
        $threadFollow = $this->user ? $thread->followedBy($this->user) : null;
        $threadLike = $this->user ? $thread->likedBy($this->user) : null;

        // Pre-load all liked post IDs for the current user in one query
        $likedPostIds = ($this->user && $thread->posts->isNotEmpty())
            ? Like::where('object_type', 'post')
                ->whereIn('object_id', $thread->posts->pluck('id'))
                ->where('user_id', $this->user->id)
                ->pluck('object_id')
                ->flip()
                ->all()
            : [];

        // Bump the view counter at the DB level. Eloquent's increment() routes
        // through the query builder's addUpdatedAtColumn(), so it would stamp
        // updated_at on every page load and make a read look like activity.
        // Reading a thread is not activity; posting or editing is.
        DB::table('threads')->where('id', $thread->id)->increment('views');
        $thread->views = $thread->views + 1;

        return view('threads.show-tw', compact('thread', 'tags', 'threadFollow', 'threadLike', 'likedPostIds'));
    }

    public function lock(int $id): RedirectResponse
    {
        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // the author, a super_admin or an admin, as for editing the thread
        $this->authorize('update', $thread);

        // call a log for this and prevent it from going out of control
        $thread->locked_by = $this->user->id;
        $thread->locked_at = Carbon::now();

        $thread->save();

        // add to activity log
        Activity::log($thread, $this->user, 8);

        return back();
    }

    public function unlock(int $id): RedirectResponse
    {
        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // the author, a super_admin or an admin, as for editing the thread
        $this->authorize('update', $thread);

        // call a log for this and prevent it from going out of control
        $thread->locked_by = null;
        $thread->locked_at = null;
        $thread->save();

        // add to activity log
        Activity::log($thread, $this->user, 9);

        return back();
    }

    public function edit(Thread $thread): View
    {
        $this->authorize('update', $thread);

        return view('threads.edit', compact('thread'))->with($this->getFormOptions());
    }

    public function update(Thread $thread, ThreadRequest $request): RedirectResponse
    {
        $msg = '';

        $this->authorize('update', $thread);

        $thread->fill($request->validated())->save();

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        $tagType = TagType::find(1);
        $tags = Tag::resolveList($tagArray, $this->user);
        $syncArray = $tags->modelKeys();
        foreach ($tags->filter(fn (Tag $tag) => $tag->wasRecentlyCreated) as $tag) {
            $msg .= ' Added tag '.$tag->name.'.';
        }

        $thread->tags()->sync($syncArray);
        $thread->syncEntities($request->input('entity_list', []));
        $thread->series()->sync($request->input('series_list', []));

        // add to activity log
        Activity::log($thread, $this->user, 2);

        flash('Success', 'Your thread has been updated');

        return redirect('threads');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @internal param int $id
     */
    public function destroy(
        Thread $thread,
        Request $request
    ) {
        $this->authorize('update', $thread);

        // if not created by the user or super_admin
        if ((null !== $thread->user) && ($thread->created_by !== auth()->id()) && !$this->user->hasGroup('super_admin')) {
            if ($request->wantsJson()) {
                return response(['status' => 'Permission Denied'], 403);
            }

            return redirect('/login');
        }

        // add to activity log
        Activity::log($thread, $this->user, 3);

        $thread->delete();

        flash()->success('Success', 'Your thread has been deleted!');

        return redirect('threads');
    }

    public function follow(int $id, Request $request): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // add the following response; a double submit must not add a second row (#2341)
        $follow = Follow::firstOrCreate(['object_id' => $thread->id, 'user_id' => $this->user->id, 'object_type' => 'thread']);

        if ($follow->wasRecentlyCreated) {
            Log::info('User '.$id.' is following '.$thread->name);
        }

        flash()->success('Success', 'You are now following the thread - '.$thread->name);

        return back();
    }

    public function unfollow(int $id, Request $request): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // delete the follow, including any duplicates left by older double submits
        Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'thread')->delete();

        flash()->success('Success', 'You are no longer following the thread.');

        return back();
    }

    public function like(int $id, Request $request): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        // the same rule as the thread page
        if (!($thread = Thread::find($id)) || !$thread->isVisibleTo($this->user)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        $thread->addLike($this->user);

        flash()->success('Success', 'You are now liking the thread - '.$thread->name);

        return back();
    }

    /**
     * Mark user as unliking the thread.
     *
     * @throws \Exception
     */
    public function unlike(int $id): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        $thread->removeLike($this->user);

        flash()->success('Success', 'You are no longer liking the thread.');

        return back();
    }

    protected function getFilterOptions(): array
    {
        return [
            'userOptions' => ['' => '&nbsp;'] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
            'seriesOptions' => ['' => ''] + Series::visible($this->user)->orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
        ];
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['threads.name' => 'Name', 'users.name' => 'User', 'threads.created_at' => 'Created At', 'threads.updated_at' => 'Updated At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }
}
