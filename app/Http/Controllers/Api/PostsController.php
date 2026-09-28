<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyFollowers;
use App\Filters\PostFilters;
use App\Http\Requests\PostPatchRequest;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
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
    ): JsonResponse {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        return response()->json(new PostCollection($posts));
    }

    /**
     * Filter a list of posts.
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

        return response()->json(new PostCollection($posts));
    }

    /**
     * Add a post to the thread given by thread_id.
     */
    public function store(Request $request): JsonResponse
    {
        // POST api/posts has no {thread} segment, so the thread comes from the body
        $request->validate([
            'thread_id' => 'required|exists:threads,id',
            'body' => 'required|min:3',
        ]);

        $thread = Thread::findOrFail($request->input('thread_id'));

        // TODO change this to use the trust_post permission to allow html
        if (auth()->id() === config('app.superuser')) {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

        $tags = Tag::resolveList($request->input('tag_list', []), $request->user());

        $post = $thread->addPost([
            'body' => $request->input('body'),
            'created_by' => auth()->id(),
            'visibility_id' => 1,
            'allow_html' => $allow_html,
        ]);

        $post->tags()->sync($tags->modelKeys());

        // here, notify anybody following the thread
        NotifyFollowers::dispatch($post);

        // add to activity log
        Activity::log($post, $this->user, 1);

        return response()->json(new PostResource($post), 201);
    }

    /**
     * Display the specified resource.
     *
     * @internal param int $id
     */
    public function show(Post $post): JsonResponse
    {
        if (Gate::denies('show_forum')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(new PostResource($post));
    }

    /**
     * PUT: full replacement of the resource.
     *
     * Optional fillable scalars omitted from the body are reset to null, and
     * relations (tags, entities) sync to the supplied arrays — missing keys
     * mean "detach all".
     */
    public function update(Post $post, PostRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (!$post->ownedBy($this->user)) {
            return $this->unauthorized($request);
        }

        $input = $request->validated();

        foreach (['name', 'slug', 'description'] as $field) {
            if (!array_key_exists($field, $input)) {
                $input[$field] = null;
            }
        }

        $post->fill($input)->save();

        $post->tags()->sync($this->resolveTagIds($request->input('tag_list', []), $request->user()));
        $post->entities()->sync($request->input('entity_list', []));

        Activity::log($post, $this->user, 2);

        return response()->json($post);
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched;
     * scalars and relations not in the request are left untouched.
     */
    public function patch(Post $post, PostPatchRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (!$post->ownedBy($this->user)) {
            return $this->unauthorized($request);
        }

        $input = $request->validated();

        $scalarInput = array_intersect_key($input, array_flip($post->getFillable()));
        if (!empty($scalarInput)) {
            $post->fill($scalarInput)->save();
        }

        if ($request->has('tag_list')) {
            $post->tags()->sync($this->resolveTagIds((array) $request->input('tag_list', []), $request->user()));
        }

        if ($request->has('entity_list')) {
            $post->entities()->sync((array) $request->input('entity_list', []));
        }

        Activity::log($post, $this->user, 2);

        return response()->json($post);
    }

    /**
     * Resolve a request tag_list (existing ids and/or names) to tag ids,
     * reusing existing tags by id or slug and creating only what is missing.
     */
    private function resolveTagIds(array $tagArray, ?User $user): array
    {
        return Tag::resolveList($tagArray, $user)->modelKeys();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     *
     * @internal param int $id
     */
    public function destroy(Post $post): JsonResponse
    {
        if ($this->user->cannot('destroy', $post)) {
            return response()->json(['message' => 'You are not authorized to delete this post.'], 403);
        }

        // add to activity log
        Activity::log($post, $this->user, 3);

        $post->delete();

        return response()->json([], 204);
    }

    protected function unauthorized(PostRequest $request): JsonResponse
    {
        return response()->json(['message' => 'Not authorized'], 403);
    }

}
