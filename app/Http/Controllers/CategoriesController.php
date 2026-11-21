<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\ThreadCategoryFilters;
use App\Models\Activity;
use App\Models\ThreadCategory;
use App\Http\Requests\ThreadCategoryRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Action;
use App\Models\Forum;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriesController extends Controller
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
    protected ThreadCategoryFilters $filter;

    public function __construct(ThreadCategoryFilters $filter)
    {
        // site configuration: every write is admin-only (#2163)
        $this->middleware(['auth', 'can:admin'], ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.categories.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultSortCriteria = ['thread_categories.created_at' => 'desc'];

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
            'internal_category',
            'internal_category_index',
            action([CategoriesController::class, 'index']),
            $this->filter,
            ThreadCategory::query()->select('thread_categories.*'),
            $this->defaultSortCriteria,
            defaultLimit: $this->defaultLimit,
        );

        $categories = $listResultSet->getList()->paginate($listResultSet->getLimit());

        return view('categories.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
            ->with(compact('categories'))
            ->render();
    }

    /**
     * Display a listing of all categories.
     *
     * Backs the `categories/all` route, mirroring index() (EVENTREPO-VX: the
     * route referenced a non-existent indexAll method).
     */
    public function indexAll(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
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
        $category = new ThreadCategory();

        return view('categories.create-tw', compact('category'))
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @internal param Request $request
     */
    public function store(ThreadCategoryRequest $request, ThreadCategory $category): RedirectResponse
    {
        $msg = '';


        $category = $category->create($request->validated());

        flash()->success('Success', 'Your category has been created');

        // add to activity log
        Activity::log($category, $this->user, Action::CREATE);

        return redirect()->route('categories.index');
    }

    /**
    * Display the specified resource.
    *
    * @internal param int $id
    */
    public function show(ThreadCategory $category): View
    {
        return view('categories.show-tw', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ThreadCategory $category): View
    {
        $this->middleware('auth');

        return view('categories.edit-tw', compact('category'))
            ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ThreadCategory $category, ThreadCategoryRequest $request): RedirectResponse
    {
        $msg = '';

        $category->fill($request->validated())->save();

        // add to activity log
        Activity::log($category, $this->user, Action::UPDATE);

        flash('Success', 'Your category has been updated');

        return redirect()->route('categories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     *
     * @internal param int $id
     */
    public function destroy(ThreadCategory $category): RedirectResponse
    {
        if ($this->user->cannot('destroy', $category)) {
            flash('Error', 'Your are not authorized to delete the category.');

            return redirect()->route('categories.index');
        }

        // add to activity log
        Activity::log($category, $this->user, Action::DELETE);

        $category->delete();

        flash()->success('Success', 'Your category has been deleted!');

        return redirect()->route('categories.index');
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
        $this->clearList($listParamSessionStore, 'internal_category', $request->get('key') ?? 'internal_category_index', filters: false);

        return redirect()->route('categories.index');
    }

    /**
     * Reset the filtering of category.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_category', $request->get('key') ?? 'internal_category_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'categories.index'));
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['categories.name' => 'Name', 'categories.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }

    protected function getFilterOptions(): array
    {
        return  [
            'forumOptions' => ['' => '&nbsp;'] + Forum::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilityOptions' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
            'forumOptions' => Forum::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }
}
