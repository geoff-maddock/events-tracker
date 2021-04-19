<?php

namespace App\Http\Controllers;

use App\Filters\BlogFilters;
use App\Models\Activity;
use App\Models\Blog;
use App\Models\ContentType;
use App\Models\Entity;
use App\Http\Requests\BlogRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Like;
use App\Models\Menu;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class BlogsController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortOrder;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected bool $hasFilter;

    // this is the class specifying the filters methods for each field
    protected BlogFilters $filter;

    public function __construct(BlogFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

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

        $this->hasFilter = false;

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
        $blogs = $query->visible($this->user)->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('blogs.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters()
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('blogs'))
            ->render();
    }

    /**
     * Display a listing of the resource.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
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
        $blogs = $query->visible($this->user)->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('blogs.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters()
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('blogs'))
            ->render();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $blog = new Blog();
        $blog->contentType = ContentType::find(ContentType::PLAIN_TEXT);
        $blog->visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        return view('blogs.create', compact('blog'))
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param Request $request
     */
    public function store(BlogRequest $request, Blog $blog)
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
        Activity::log($blog, $this->user, 1);

        return redirect()->route('blogs.index');
    }

    /**
     * @param Blog $blog
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($blog)
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

                        $m->to($user->email, $user->name)->subject($site . ': ' . $tag->name . ' :: ' . $blog->created_at->format('D F jS') . ' ' . $blog->name);
                    });
                    $users[$user->id] = $tag->name;
                }
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function show(Blog $blog)
    {
        return view('blogs.show', compact('blog'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Blog $blog)
    {
        $this->middleware('auth');

        return view('blogs.edit', compact('blog'))
            ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Blog $blog, BlogRequest $request): RedirectResponse
    {
        $msg = '';

        $blog->fill($request->input())->save();

        if (!$blog->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!DB::table('tags')->where('id', $tag)->get()) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $blog->tags()->sync($syncArray);
        $blog->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($blog, $this->user, 2);

        flash('Success', 'Your blog has been updated');

        return redirect()->route('blogs.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     *
     * @internal param int $id
     */
    public function destroy(Blog $blog)
    {
        if ($this->user->cannot('destroy', $blog)) {
            flash('Error', 'Your are not authorized to delete the blog.');

            return redirect()->route('blogs.index');
        }

        // add to activity log
        Activity::log($blog, $this->user, 3);

        $blog->delete();

        flash()->success('Success', 'Your blog has been deleted!');

        return redirect()->route('blogs.index');
    }

    /**
     * Mark user as liking the blog.
     *
     * @return Response
     */
    public function like($id, Request $request)
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

        Log::info('User ' . $id . ' is liking ' . $blog->name);

        flash()->success('Success', 'You are now liking the selected blog.');

        return back();
    }

    /**
     * Mark user as unliking the blog.
     */
    public function unlike(int $id, Request $request): RedirectResponse
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

        // update the likes
        --$blog->likes;
        $blog->save();

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'blog')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer liking the blog.');

        return back();
    }

    /**
     * Reset the rpp, sort, order
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
     *
     * @return Response
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ) {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_blog_index';
        $listParamSessionStore->setBaseIndex('internal_blog');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'blogs.index');
    }

    protected function unauthorized(Request $request): RedirectResponse
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['blogs.name' => 'Name', 'blogs.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }

    protected function getFilterOptions(): array
    {
        return  [
            'userOptions' => ['' => '&nbsp;'] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilityOptions' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
            'tagOptions' => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityOptions' => Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'menuOptions' => ['' => ''] + Menu::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'contentTypeOptions' => ['' => ''] + ContentType::orderBy('name', 'ASC')->pluck('name', 'id')->all()
        ];
    }
}
