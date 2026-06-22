<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\BlogFilters;
use App\Http\Requests\BlogPatchRequest;
use App\Http\Requests\BlogRequest;
use App\Http\Resources\BlogCollection;
use App\Http\Resources\BlogResource;
use Illuminate\Http\JsonResponse;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Blog;
use App\Models\Like;
use App\Models\Tag;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Str;
use App\Models\Action;

class BlogsController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected BlogFilters $filter;

    public function __construct(BlogFilters $filter)
    {
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.blogs.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultSortCriteria = ['blogs.created_at' => 'desc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

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
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_blog');
        $listParamSessionStore->setKeyPrefix('internal_blog_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([BlogsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Blog::query()->select('blogs.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultLimit($this->defaultLimit)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the blogs
        $blogs = $query->paginate($listResultSet->getLimit());

        return response()->json(new BlogCollection($blogs));
    }

    /**
     * Filtered listing of the resource (JSON).
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_blog');
        $listParamSessionStore->setKeyPrefix('internal_blog_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([BlogsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Blog::query()->select('blogs.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultLimit($this->defaultLimit)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // query and paginate the blogs
        /* @phpstan-ignore-next-line */
        $blogs = $query->visible($this->user)->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        return response()->json(new BlogCollection($blogs));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @internal param Request $request
     */
    public function store(BlogRequest $request, Blog $blog): JsonResponse
    {
        // TODO change this to use the trust_blog permission to allow html
        if (auth()->id() === config('app.superuser')) {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

        $msg = '';

        $input = $request->all();

        $blog = $blog->create($input);

        flash()->success('Success', 'Your blog has been created');

        // here, notify anybody following the blog
        // $this->notifyFollowing($blog);

        // add to activity log
        Activity::log($blog, $this->user, Action::CREATE);

        return response()->json($blog);
    }

    /**
     * @param Blog $blog
     */
    protected function notifyFollowing($blog): RedirectResponse
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $blog->tags()->get();
        $users = [];

        // notify users following any tags related to the blog

        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following-thread', ['user' => $user, 'blog' => $blog, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $blog, $tag, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$blog->created_at->format('D F jS').' '.$blog->name);
                    });
                    $users[$user->id] = $tag->name;
                }
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog): JsonResponse
    {
        return response()->json($blog);
    }

    /**
     * PUT: full replacement of the resource.
     *
     * Optional fillable scalars omitted from the body are reset to null, and
     * relations (tags, entities) sync to the supplied arrays — missing keys
     * mean "detach all".
     */
    public function update(Blog $blog, BlogRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (!$blog->ownedBy($this->user)) {
            return $this->unauthorized($request);
        }

        $input = $request->all();

        // Reset truly optional (nullable) fields not supplied in the body.
        if (!array_key_exists('menu_id', $input)) {
            $input['menu_id'] = null;
        }

        $blog->fill($input)->save();

        $blog->tags()->sync($this->resolveTagIds($request->input('tag_list', [])));
        $blog->entities()->sync($request->input('entity_list', []));

        Activity::log($blog, $this->user, Action::UPDATE);

        return response()->json($blog);
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched;
     * scalars and relations not in the request are left untouched.
     */
    public function patch(Blog $blog, BlogPatchRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (!$blog->ownedBy($this->user)) {
            return $this->unauthorized($request);
        }

        $input = $request->all();

        $scalarInput = array_intersect_key($input, array_flip($blog->getFillable()));
        if (!empty($scalarInput)) {
            $blog->fill($scalarInput)->save();
        }

        if ($request->has('tag_list')) {
            $blog->tags()->sync($this->resolveTagIds((array) $request->input('tag_list', [])));
        }

        if ($request->has('entity_list')) {
            $blog->entities()->sync((array) $request->input('entity_list', []));
        }

        Activity::log($blog, $this->user, Action::UPDATE);

        return response()->json($blog);
    }

    /**
     * Resolve a list of tag identifiers to ids, creating any tags that don't
     * already exist by id. Accepts a mix of existing tag ids and new tag names.
     */
    private function resolveTagIds(array $tagArray): array
    {
        $syncArray = [];

        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();

                Activity::log($newTag, $this->user, Action::CREATE);

                $syncArray[strtolower($tag)] = $newTag->id;
            } else {
                $syncArray[$key] = $tag;
            }
        }

        return $syncArray;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     *
     * @internal param int $id
     */
    public function destroy(Blog $blog): JsonResponse
    {
        if ($this->user->cannot('destroy', $blog)) {
            return response()->json(['message' => 'Not authorized to delete the blog.'], 403);
        }

        // add to activity log
        Activity::log($blog, $this->user, Action::DELETE);

        $blog->delete();

        return response()->json([], 204);
    }

    /**
     * Mark user as liking the blog.
     */
    public function like(int $id): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$blog = Blog::find($id)) {
            flash()->error('Error', 'No such blog');

            return back();
        }

        // add the like response
        $like = new Like();
        $like->object_id = $id;
        $like->user()->associate($this->user);
        $like->object_type = 'blog';
        $like->save();

        // update the likes
        ++$blog->likes;
        $blog->save();

        // log the like
        // Log::info('User '.$id.' is liking '.$blog->name);

        flash()->success('Success', 'You are now liking the selected blog.');

        return back();
    }

    /**
     * Mark user as unliking the blog.
     */
    public function unlike(int $id): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$blog = Blog::find($id)) {
            flash()->error('Error', 'No such blog');

            return back();
        }

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'blog')->first();
        if ($response) {
            $response->delete();

            // update the likes
            --$blog->likes;
            $blog->save();
        }

        flash()->success('Success', 'You are no longer liking the blog.');

        return back();
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
        $keyPrefix = $request->get('key') ?? 'internal_blog_index';
        $listParamSessionStore->setBaseIndex('internal_blog');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear all sort
        $listParamSessionStore->clearSort();

        return redirect()->route('blogs.index');
    }

    /**
     * Reset the filtering of blogs.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_blog_index';
        $listParamSessionStore->setBaseIndex('internal_blog');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'blogs.index');
    }

    protected function unauthorized(Request $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

}
