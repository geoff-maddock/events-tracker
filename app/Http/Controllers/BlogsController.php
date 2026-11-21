<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\BlogFilters;
use App\Http\Requests\BlogRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Blog;
use App\Models\ContentType;
use App\Models\Entity;
use App\Models\Menu;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\ImageHandler;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use App\Models\Action;

class BlogsController extends Controller
{
    use BuildsListPages;

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
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

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
    ): string {
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_blog',
            'internal_blog_index',
            action([BlogsController::class, 'index']),
            $this->filter,
            Blog::query()->select('blogs.*')->with(['user', 'photos']),
            $this->defaultSortCriteria,
            defaultLimit: $this->defaultLimit,
        );

        /* @phpstan-ignore-next-line */
        $blogs = $listResultSet->getList()->visible($this->user)->paginate($listResultSet->getLimit());

        return view('blogs.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
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
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $blog = new Blog();
        $blog->contentType = ContentType::find(ContentType::PLAIN_TEXT);
        $blog->visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        return view('blogs.create-tw', compact('blog'))
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @internal param Request $request
     */
    public function store(BlogRequest $request, Blog $blog): RedirectResponse
    {
        // TODO  https://github.com/geoff-maddock/events-tracker/issues/1412
        // change this to use the trust_blog permission to allow html
        if (auth()->id() === config('app.superuser')) {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

        $msg = '';

        $input = $request->validated();

        $blog = $blog->create($input);

        flash()->success('Success', 'Your blog has been created');

        // add to activity log
        Activity::log($blog, $this->user, Action::CREATE);

        return redirect()->route('blogs.index');
    }

    /**
     * Display the specified resource.
     *
     * @internal param int $id
     */
    public function show(Blog $blog): View
    {
        abort_unless($blog->isVisibleTo($this->user), 404);

        // eager-load relations consumed by the page + JSON-LD
        $blog->loadMissing(['user', 'photos']);

        return view('blogs.show-tw', compact('blog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blog $blog): View
    {
        $this->authorize('update', $blog);

        return view('blogs.edit-tw', compact('blog'))
            ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Blog $blog, BlogRequest $request): RedirectResponse
    {
        $msg = '';

        $this->authorize('update', $blog);

        $blog->fill($request->validated())->save();

        $tagArray = $request->input('tag_list', []);
        $tags = Tag::resolveList($tagArray, $this->user);
        $syncArray = $tags->modelKeys();
        foreach ($tags->filter(fn (Tag $tag) => $tag->wasRecentlyCreated) as $tag) {
            $msg .= ' Added tag '.$tag->name.'.';
        }

        $blog->tags()->sync($syncArray);
        $blog->syncEntities($request->input('entity_list', []));

        // add to activity log
        Activity::log($blog, $this->user, Action::UPDATE);

        flash('Success', 'Your blog has been updated');

        return redirect()->route('blogs.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     *
     * @internal param int $id
     */
    public function destroy(Blog $blog): RedirectResponse
    {
        if ($this->user->cannot('destroy', $blog)) {
            flash('Error', 'Your are not authorized to delete the blog.');

            return redirect()->route('blogs.index');
        }

        // add to activity log
        Activity::log($blog, $this->user, Action::DELETE);

        $blog->delete();

        flash()->success('Success', 'Your blog has been deleted!');

        return redirect()->route('blogs.index');
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
        $this->clearList($listParamSessionStore, 'internal_blog', $request->get('key') ?? 'internal_blog_index', filters: false);

        return redirect()->route('blogs.index');
    }

    /**
     * Reset the filtering of blogs.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_blog', $request->get('key') ?? 'internal_blog_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'blogs.index'));
    }

    /**
     * Add a photo to a blog.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): void
    {
        abort_unless($this->user !== null, 401);
        // matches the upload form in blogs/show-tw: the author or a super_admin
        $blog = Blog::findOrFail($id);
        abort_unless($this->user->hasGroup('super_admin') || $this->user->can('update', $blog), 403);

        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp|max:5120', // KB; matches Dropzone maxFilesize
        ]);

        if ($blog = Blog::find($id)) {
            $photo = $imageHandler->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if (0 === count($blog->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            $blog->addPhoto($photo);
        }
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['blogs.name' => 'Name', 'blogs.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'userOptions' => ['' => '&nbsp;'] + Cache::remember(User::FILTER_OPTIONS_CACHE_KEY, 3600, fn () => User::orderBy('name', 'ASC')->pluck('name', 'name')->all()),
            'tagOptions' => ['' => '&nbsp;'] + Cache::remember('filter-opts-tags-slug', 3600, fn () => Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all()),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilityOptions' => ['' => ''] + Cache::remember('form-opts-visibilities', 86400, fn () => Visibility::pluck('name', 'id')->all()),
            'tagOptions' => Cache::remember('form-opts-tags', 3600, fn () => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all()),
            'entityOptions' => Cache::remember(Entity::optionCacheKey('form-opts-entities-all'), 3600, fn () => Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all()),
            'menuOptions' => ['' => ''] + Cache::remember('form-opts-menus', 3600, fn () => Menu::orderBy('name', 'ASC')->pluck('name', 'id')->all()),
            'contentTypeOptions' => ['' => ''] + Cache::remember('form-opts-content-types', 86400, fn () => ContentType::orderBy('name', 'ASC')->pluck('name', 'id')->all()),
        ];
    }
}
