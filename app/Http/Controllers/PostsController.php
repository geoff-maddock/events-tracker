<?php

namespace App\Http\Controllers;

use App\Filters\PostFilters;
use App\Http\Requests\PostRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Mail\FollowingPostUpdate;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\StringHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Str;
use Symfony\Component\HttpFoundation\Response;

class PostsController extends Controller
{
    protected Post $post;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected bool $hasFilter;

    protected array $filters;

    // array of sort criteria to be applied in order
    protected array $defaultSortCriteria;

    protected PostFilters $filter;

    public function __construct(PostFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // prefix for session storage
        $this->prefix = 'app.posts.';

        // default list variables - move to function that set from session or default
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultLimit = 10;

        // set list variables
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['posts.created_at', 'desc'];
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
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_post');
        $listParamSessionStore->setKeyPrefix('internal_post_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PostsController::class, 'index']));

        $baseQuery = Post::query()
        ->leftJoin('users', 'posts.created_by', '=', 'users.id')
        ->select('posts.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['posts.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        /* @phpstan-ignore-next-line */
        $posts = $query->visible($this->user)
            ->with('visibility')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $posts;
        }

        return view('posts.index')
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
        ->with(compact('posts'))
        ->render();
    }

    /**
     * Filter a list of posts.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        // initialized listParamSessionStore with base index key
        $listParamSessionStore->setBaseIndex('internal_post');
        $listParamSessionStore->setKeyPrefix('internal_post_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PostsController::class, 'index']));

        $baseQuery = Post::query()
        ->leftJoin('users', 'posts.created_by', '=', 'users.id')
        ->select('posts.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['posts.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        /* @phpstan-ignore-next-line */
        $posts = $query->visible($this->user)
        ->with('visibility')
        ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $posts;
        }

        return view('posts.index')
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
        ->with(compact('posts'))->render();
    }

    /**
     * Display a listing of posts by tag.
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug,
        StringHelper $stringHelper
    ): string {
        // convert the slug to name
        $tag = $stringHelper->SlugToName($slug);

        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_post');
        $listParamSessionStore->setKeyPrefix('internal_post_tags');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PostsController::class, 'index']));

        $baseQuery = Post::query()
        ->leftJoin('users', 'posts.created_by', '=', 'users.id')
        ->select('posts.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['posts.created_at' => 'desc'])
            ->setParentFilter(['tag' => $slug]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        /* @phpstan-ignore-next-line */
        $posts = $query->visible($this->user)
        ->with('visibility')
        ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // return json only
        if (request()->wantsJson()) {
            return $posts;
        }

        return view('posts.index')
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
        ->with(compact('posts'))->render();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     *
     * @internal param Request $request
     */
    public function store(Request $request, Thread $thread)
    {
        $msg = '';

        // TODO change this to use the trust_post permission to allow html
        if (auth()->id() === config('app.superuser')) {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

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

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $thread->addPost([
            'body' => request('body'),
            'created_by' => auth()->id(),
            'visibility_id' => 1,
            'allow_html' => $allow_html,
        ]);

        $post = Post::where('thread_id', '=', $thread->id)->orderBy('id', 'DESC')->first();

        $post->tags()->sync($syncArray);

        // here, notify anybody following the thread
        $this->notifyFollowing($post);

        // add to activity log
        Activity::log($post, $this->user, 1);

        return back();
    }

    /**
     * @param Post $post
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($post)
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        $thread = $post->thread;

        // notify users following any of the tags
        $tags = $thread->tags()->get();
        $users = [];

        // notify users who are following this thread
        foreach ($thread->followers() as $user) {
            // if the user does not have this setting, continue
            if ($user?->profile?->setting_forum_update !== 1) {
                continue;
            }
            // if the user hasn't already been notified, then email them
            if (!array_key_exists($user->id, $users)) {
                Mail::to($user->email)->send(new FollowingPostUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $post));
                $users[$user->id] = $thread->name;
            }
        }

        // notify users following any tags related to the thread
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::to($user->email)->send(new FollowingPostUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $post, $tag));
                    $users[$user->id] = $tag->name;
                }
            }
        }

        // notify users following any of the series
        $seriess = $thread->series()->get();

        foreach ($seriess as $series) {
            foreach ($series->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }

                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::to($user->email)->send(new FollowingPostUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $post));
                    $users[$user->id] = $series->name;
                }
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @internal param int $id
     */
    public function show(Post $post): RedirectResponse
    {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        // call a log for this and prevent it from going out of control
        ++$post->views;
        $post->save();

        $route = route('threads.show', ['thread' => $post->thread_id]).'#post-'.$post->id;

        return redirect($route);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post): View
    {
        $this->middleware('auth');

        return view('posts.edit', compact('post'))->with($this->getFormOptions());
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'tagOptions' => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityOptions' => Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @internal param int $id
     */
    public function update(Post $post, PostRequest $request): RedirectResponse
    {
        $msg = '';

        $post->fill($request->input())->save();

        if (!$post->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

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

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $post->tags()->sync($syncArray);
        $post->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($post, $this->user, 2);

        flash('Success', 'Your post has been updated');

        return redirect()->route('threads.show', ['thread' => $post->thread_id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     *
     * @internal param int $id
     */
    public function destroy(Post $post): RedirectResponse
    {
        $id = $post->thread_id;
        $thread = $post->thread;

        if ($this->user->cannot('destroy', $post)) {
            flash('Error', 'Your are not authorized to delete the post.');

            return redirect()->route('threads.show', ['thread' => $id]);
        }

        // add to activity log
        Activity::log($post, $this->user, 3);

        $post->delete();

        flash()->success('Success', 'Your post has been deleted!');

        return redirect()->route('threads.show', ['thread' => $id]);
    }

    /**
     * Mark user as liking the post.
     */
    public function like(int $id, Request $request): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$post = Post::find($id)) {
            flash()->error('Error', 'No such post');

            return back();
        }

        // add the like response
        $like = new Like();
        $like->object_id = $id;
        $like->user()->associate($this->user);
        $like->object_type = 'post';
        $like->save();

        // update the likes
        ++$post->likes;
        $post->save();

        Log::info('User '.$id.' is liking '.$post->name);

        flash()->success('Success', 'You are now liking the selected post.');

        return back();
    }

    /**
     * Mark user as unliking the post.
     *
     * @return Response
     */
    public function unlike(int $id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$post = Post::find($id)) {
            flash()->error('Error', 'No such post');

            return back();
        }

        // update the likes
        --$post->likes;
        $post->save();

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'post')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer liking the post.');

        return back();
    }

    protected function unauthorized(PostRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    /**
     * Reset the limit, sort, direction.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the limit, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_post_index';
        $listParamSessionStore->setBaseIndex('internal_post');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('posts.index');
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
        $keyPrefix = $request->get('key') ?? 'internal_post_index';
        $listParamSessionStore->setBaseIndex('internal_post');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'posts.index');
    }

    protected function getFilterOptions(): array
    {
        return [
            'userOptions' => ['' => '&nbsp;'] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
        ];
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['posts.name' => 'Name', 'users.name' => 'User', 'posts.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }
}
