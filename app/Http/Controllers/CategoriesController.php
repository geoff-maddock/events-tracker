<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Redirect;
use Str;

class CategoriesController extends Controller
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

    protected bool $hasFilter;

    // this is the class specifying the filters methods for each field
    protected ThreadCategoryFilters $filter;

    public function __construct(ThreadCategoryFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

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
        $listParamSessionStore->setBaseIndex('internal_category');
        $listParamSessionStore->setKeyPrefix('internal_category_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([CategoriesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = ThreadCategory::query()->select('thread_categories.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultLimit($this->defaultLimit)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // query and paginate the categories
        $categories = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('categories.index-tw')
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
            ->with(compact('categories'))
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
        $listParamSessionStore->setBaseIndex('internal_category');
        $listParamSessionStore->setKeyPrefix('internal_category_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([CategoriesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = ThreadCategory::query()->select('thread_categories.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultLimit($this->defaultLimit)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // query and paginate the categories
        $categories = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('categories.index-tw')
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
            ->with(compact('categories'))
            ->render();
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

        $input = $request->all();

        $category = $category->create($input);

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

        $category->fill($request->input())->save();

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
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_category_index';
        $listParamSessionStore->setBaseIndex('internal_category');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear all sort
        $listParamSessionStore->clearSort();

        return redirect()->route('categories.index');
    }

    /**
     * Reset the filtering of category.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_category_index';
        $listParamSessionStore->setBaseIndex('internal_category');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'categories.index');
    }

    protected function unauthorized(Request $request): RedirectResponse | Response
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
