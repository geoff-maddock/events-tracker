<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\BlogFilters;
use App\Http\Requests\BlogPatchRequest;
use App\Http\Requests\BlogRequest;
use App\Http\Resources\BlogCollection;
use Illuminate\Http\JsonResponse;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Blog;
use App\Models\Tag;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        /* @phpstan-ignore-next-line */
        $blogs = $query->visible($this->user)->paginate($listResultSet->getLimit());

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

        // add to activity log
        Activity::log($blog, $this->user, Action::CREATE);

        return response()->json($blog);
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
        return Tag::resolveList($tagArray, $this->user)->modelKeys();
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

    protected function unauthorized(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not authorized'], 403);
    }

}
