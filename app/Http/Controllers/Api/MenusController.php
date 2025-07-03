<?php

namespace App\Http\Controllers\Api;

use App\Filters\MenuFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Resources\MenuCollection;
use App\Http\Resources\MenuResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Menu;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenusController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected bool $hasFilter;

    protected MenuFilters $filter;

    public function __construct(MenuFilters $filter)
    {
        $this->filter = $filter;

        $this->prefix = 'app.menus.';
        $this->defaultLimit = 5;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['menus.name' => 'asc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;

        parent::__construct();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $listParamSessionStore->setBaseIndex('internal_menu');
        $listParamSessionStore->setKeyPrefix('internal_menu_index');
        $listParamSessionStore->setIndexTab(action([MenusController::class, 'index']));

        $baseQuery = Menu::query()->select('menus.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort($this->defaultSortCriteria);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        $query = $listResultSet->getList();

        $menus = $query->paginate($listResultSet->getLimit());

        return response()->json(new MenuCollection($menus));
    }

    public function show(Menu $menu): JsonResponse
    {
        return response()->json(new MenuResource($menu));
    }

    public function store(MenuRequest $request, Menu $menu): JsonResponse
    {
        $menu = $menu->create($request->all());

        return response()->json(new MenuResource($menu), 201);
    }

    public function update(Menu $menu, MenuRequest $request): JsonResponse
    {
        $menu->fill($request->all())->save();

        return response()->json(new MenuResource($menu));
    }

    public function destroy(Menu $menu): JsonResponse
    {
        $menu->delete();

        return response()->json([], 204);
    }
}
