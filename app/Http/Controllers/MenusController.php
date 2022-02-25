<?php

namespace App\Http\Controllers;

use App\Filters\MenuFilters;
use App\Http\Requests\MenuRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Menu;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redirect;

class MenusController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    // array of sort criteria to be applied in order
    protected array $defaultSortCriteria;

    protected array $filters;

    protected bool $hasFilter;

    protected MenuFilters $filter;

    public function __construct(MenuFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.menus.';

        // default list variables
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultLimit = 10;

        // set list variables
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['menu.name' => 'desc'];

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
        $listParamSessionStore->setBaseIndex('internal_menu');
        $listParamSessionStore->setKeyPrefix('internal_menu_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([MenusController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Menu::query()->select('menus.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort([$this->defaultSort => $this->defaultSortDirection]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // query and paginate the menus
        // @phpstan-ignore-next-line
        $menus = $query->visible($this->user)->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('menus.index')
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
            ->with(compact('menus'))
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
        $listParamSessionStore->setBaseIndex('internal_menu');
        $listParamSessionStore->setKeyPrefix('internal_menu_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([MenusController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Menu::query()->select('menus.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort([$this->defaultSort => $this->defaultSortDirection]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // query and paginate the blogs
        $menus = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('menus.index')
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
            ->with(compact('menus'))
            ->render();
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
        $keyPrefix = $request->get('key') ?? 'internal_menu_index';
        $listParamSessionStore->setBaseIndex('internal_menu');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('menus.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_menu_index';
        $listParamSessionStore->setBaseIndex('internal_menu');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'menus.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $menu = new Menu();

        $menu->visibility = Visibility::find(Visibility::VISIBILITY_PUBLIC);

        return view('menus.create', compact('menu'))->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $msg = '';

        $input = $request->all();

        $menu = $menu->create($input);

        flash()->success('Success', 'Your menu has been created');

        return redirect()->route('menus.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu): View
    {
        return view('menus.show', compact('menu'));
    }

    /**
     * Display the specified menu content.
     */
    public function content(int $id, Request $request): View | RedirectResponse
    {
        // get the menu
        if (!$menu = Menu::find($id)) {
            flash()->error('Error', 'No such menu');

            return back();
        }

        // todo - confirm the menu is visible

        return view('menus.content', compact('menu'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu): View
    {
        $this->middleware('auth');

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $parents = ['' => ''] + Menu::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('menus.edit', compact('menu'))->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Menu $menu, MenuRequest $request): RedirectResponse
    {
        $msg = '';

        $menu->fill($request->input())->save();

        return redirect('menus');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect('menus');
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['menus.name' => 'Name', 'menus.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'visibilityOptions' => ['' => '&nbsp;'] + Visibility::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'sortOrderOptions' => ['' => '', 'asc' => 'asc', 'desc' => 'desc'],
            'visibilityOptions' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
            'menuOptions' => ['' => ''] + Menu::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }
}
