<?php

namespace App\Http\Controllers;

use App\Filters\ThreadFilters;
use App\Http\Requests\ThreadRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Mail\FollowingThreadUpdate;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class ThreadsController extends Controller
{
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

    protected bool $hasFilter;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected ThreadFilters $filter;

    protected array $criteria;

    public function __construct(ThreadFilters $filter)
    {
        $this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

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
        $this->hasFilter = false;

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
        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
            ->leftJoin('users', 'threads.created_by', '=', 'users.id')
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
            ->with('visibility','entities','tags','posts','event','user')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $threads;
        }

        return view('threads.index-tw')
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
     * Display a listing of the resource that the user is following.
     *
     * @throws \Throwable
     */
    public function indexFollowing(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::join('follows', 'threads.id', '=', 'follows.object_id')
        ->where('follows.object_type', '=', 'thread')
        ->where('follows.user_id', '=', $this->user->id)
        ->orderBy('follows.created_at', 'desc')
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

        return view('threads.index-tw')
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
                ->with(['type' => 'Followed Threads'])
                ->render();
    }

    /**
     * Filter the list of threads.
     *
     * @internal param $Request
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
            ->leftJoin('users', 'threads.created_by', '=', 'users.id')
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

        return view('threads.index-tw')
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
     * Reset the limit, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the limit, sort, direction only to default values
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
     * Display a listing of the resource.
     */
    public function indexAll(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
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
            ->paginate(1000000000);

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $threads;
        }

        return view('threads.index-tw')
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
     * Display a listing of threads by tag.
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $tag
    ): string {
        $tag = urldecode($tag);
        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_tags');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
        ->select('threads.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['threads.created_at' => 'desc'])
            ->setParentFilter(['tag' => ucfirst($tag)]);

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

        return view('threads.index-tw')
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
     * Display a listing of threads by category.
     */
    public function indexCategories(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $category
    ): string {
        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_category');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()->select('threads.*');

        // configure the list entity results builder
        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['threads.created_at' => 'desc'])
            ->setParentFilter(['category' => strtolower($category)]);

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

        return view('threads.index-tw')
        ->with(array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters(),
                'tag' => $category,
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
        ->with(compact('threads'))
        ->render();
    }

    /**
     * Display a listing of threads by series.
     */
    public function indexSeries(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $series
    ): string {
        $series = urldecode($series);
        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_series');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
        ->select('threads.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['threads.created_at' => 'desc'])
            ->setParentFilter(['series' => ucfirst($series)]);

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

        return view('threads.index-tw')
        ->with(array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters(),
                'tag' => $series,
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
        ->with(compact('threads'))
        ->render();
    }

    /**
     * Display a listing of threads by entity.
     */
    public function indexRelatedTo(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $relatedTo
    ): string {
        $relatedTo = Str::title(str_replace('-', ' ', $relatedTo));

        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_thread');
        $listParamSessionStore->setKeyPrefix('internal_thread_related_to');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ThreadsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Thread::query()
        ->select('threads.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['threads.created_at' => 'desc'])
            ->setParentFilter(['related' => $relatedTo]);

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

        return view('threads.index-tw')
        ->with(array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters(),
                'tag' => $relatedTo,
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
        ->with(compact('threads'))
        ->render();
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
            'eventOptions' => ['' => ''] + Event::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'seriesOptions' => Series::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'forumOptions' => ['' => ''] + Forum::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ThreadRequest $request): RedirectResponse
    {
        $msg = '';

        // get the request
        $input = $request->all();

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();
                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;

                $msg .= ' Linked tag '.$tag.'.';
            }
        }

        $thread = Thread::create($input);

        $thread->tags()->attach($syncArray);
        $thread->entities()->attach($request->input('entity_list'));
        $thread->series()->attach($request->input('series_list'));

        // here, make a call to notify all users who are following any of the sync'd tags
        $this->notifyFollowing($thread);

        // add to activity log
        Activity::log($thread, $this->user, 1);

        flash()->success('Success', 'Your thread has been created. '.$msg);

        return redirect()->route('threads.show', compact('thread'));
    }

    protected function notifyFollowing(Thread $thread): RedirectResponse
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $thread->tags()->get();
        $users = [];

        // notify users following any tags related to the thread
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::to($user->email)
                        ->send(new FollowingThreadUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $tag));

                    $users[$user->id] = $tag->name;
                }
            }
        }

        // notify users following any of the series
        $series = $thread->series()->get();

        foreach ($series as $s) {
            foreach ($s->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::to($user->email)
                        ->send(new FollowingThreadUpdate($url, $site, $admin_email, $reply_email, $user, $thread));
                    $users[$user->id] = $s->name;
                }
            }
        }

        return back();
    }

    /**
     * Create a conversation slug.
     */
    public function makeSlugFromTitle(string $title): string
    {
        $slug = Str::slug($title);

        $count = Thread::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function show(Thread $thread): RedirectResponse | View
    {
        // if the gate does not allow this user to show a forum redirect to home
        if ($thread->forum_id && Gate::denies('show_forum', $thread->forum_id) && $thread->visibility_id !== 3) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        // call a log for this and prevent it from going out of control
        ++$thread->views;
        $thread->save();

        return view('threads.show-tw', compact('thread', 'tags'));
    }

    public function lock(int $id): RedirectResponse
    {
        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }
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
        $this->middleware('auth');

        return view('threads.edit', compact('thread'))->with($this->getFormOptions());
    }

    public function update(Thread $thread, ThreadRequest $request): RedirectResponse
    {
        $msg = '';

        $thread->fill($request->input())->save();

        if (!$thread->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        $tagType = TagType::find(1);

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();
                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $thread->tags()->sync($syncArray);
        $thread->entities()->sync($request->input('entity_list', []));
        $thread->series()->sync($request->input('series_list', []));

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

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user()->associate($this->user);
        $follow->object_type = 'thread'; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $follow->save();

        Log::info('User '.$id.' is following '.$thread->name);

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

        // delete the follow
        $response = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'thread')->first();
        $response->delete();

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

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // add the following response
        $like = new Like();
        $like->object_id = $id;
        $like->user()->associate($this->user);
        $like->object_type = 'thread';
        $like->save();

        // update the likes
        ++$thread->likes;
        $thread->save();

        Log::info('User '.$id.' is liking '.$thread->name);

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

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'thread')->first();
        $response->delete();

        // update the likes
        --$thread->likes;
        $thread->save();

        flash()->success('Success', 'You are no longer liking the thread.');

        return back();
    }

    /**
     * Get the default filters array.
     */
    public function getDefaultFilters(): array
    {
        return [];
    }

    protected function getDefaultLimitFilters(): array
    {
        return [
            'limit' => $this->defaultLimit,
            'sort' => $this->defaultSort,
            'sortDirection' => $this->defaultSortDirection,
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'userOptions' => ['' => '&nbsp;'] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
            'seriesOptions' => ['' => ''] + Series::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
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
