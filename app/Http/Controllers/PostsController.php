<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\PostFilters;
use App\Jobs\NotifyFollowers;
use App\Http\Requests\PostRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Entity;
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
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PostsController extends Controller
{
    use BuildsListPages;

    protected Post $post;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    // array of sort criteria to be applied in order
    protected array $defaultSortCriteria;

    protected PostFilters $filter;

    public function __construct(PostFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);
        // like threads: only a verified account writes content (#2312)
        $this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update']]);

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
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        return $this->postListPage($listParamSessionStore, $listEntityResultBuilder, 'internal_post_index');
    }

    /**
     * Filter a list of posts.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string|RedirectResponse {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
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
        return $this->postListPage($listParamSessionStore, $listEntityResultBuilder, 'internal_post_tags', ['tag' => $slug]);
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
        // the reply forms send only body and tag_list, so PostRequest (which also
        // requires visibility_id) doesn't fit; same body rule
        $request->validate(['body' => 'required|min:3']);

        // a locked thread hides its reply form; refuse a direct POST too (#2343)
        abort_if((bool) $thread->is_locked, 403, 'This thread has been locked.');

        $msg = '';

        // TODO change this to use the trust_post permission to allow html
        if (auth()->id() === config('app.superuser')) {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

        $tagArray = $request->input('tag_list', []);
        $tags = Tag::resolveList($tagArray, auth()->user());
        $syncArray = $tags->modelKeys();
        foreach ($tags->filter(fn (Tag $tag) => $tag->wasRecentlyCreated) as $tag) {
            $msg .= ' Added tag '.$tag->name.'.';
        }

        // use the created post: re-reading the thread's newest post could pick up
        // someone else's reply saved at the same moment
        $post = $thread->addPost([
            'body' => request('body'),
            'created_by' => auth()->id(),
            'visibility_id' => 1,
            'allow_html' => $allow_html,
        ]);

        $post->tags()->sync($syncArray);

        // here, notify anybody following the thread
        NotifyFollowers::dispatch($post);

        // add to activity log
        Activity::log($post, $this->user, 1);

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
        $this->authorize('update', $post);

        return view('posts.edit-tw', compact('post'))->with($this->getFormOptions());
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

        $this->authorize('update', $post);

        $post->fill($request->validated())->save();

        $tagArray = $request->input('tag_list', []);
        $tags = Tag::resolveList($tagArray, auth()->user());
        $syncArray = $tags->modelKeys();
        foreach ($tags->filter(fn (Tag $tag) => $tag->wasRecentlyCreated) as $tag) {
            $msg .= ' Added tag '.$tag->name.'.';
        }

        $post->tags()->sync($syncArray);
        $post->syncEntities($request->input('entity_list', []));

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

        // a post is as visible as the thread it is in
        if (!($post = Post::find($id)) || !Thread::find($post->thread_id)?->isVisibleTo($this->user)) {
            flash()->error('Error', 'No such post');

            return back();
        }

        $post->addLike($this->user);

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

        $post->removeLike($this->user);

        flash()->success('Success', 'You are no longer liking the post.');

        return back();
    }

    /**
     * The posts list every list action here renders.
     *
     * @param array<string, mixed> $parentFilter filters the page itself imposes (its tag)
     */
    private function postListPage(
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $keyPrefix,
        array $parentFilter = [],
    ): string {
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_post',
            $keyPrefix,
            action([PostsController::class, 'index']),
            $this->filter,
            Post::query()->leftJoin('users', 'posts.created_by', '=', 'users.id')->select('posts.*'),
            ['posts.created_at' => 'desc'],
            parentFilter: $parentFilter,
        );

        /* @phpstan-ignore-next-line */
        $posts = $listResultSet->getList()->visible($this->user)
            ->with('visibility')
            ->paginate($listResultSet->getLimit());

        return view('posts.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
            ->with(compact('posts'))
            ->render();
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
        $this->clearList($listParamSessionStore, 'internal_post', $request->get('key') ?? 'internal_post_index', filters: false);

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
        $this->clearList($listParamSessionStore, 'internal_post', $request->get('key') ?? 'internal_post_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'posts.index'));
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
