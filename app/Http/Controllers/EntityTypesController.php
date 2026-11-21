<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\EntityTypeFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntityTypeRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\EntityType;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EntityTypesController extends Controller
{
    use BuildsListPages;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected string $prefix;

    protected EntityTypeFilters $filter;

    public function __construct(EntityTypeFilters $filter)
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
        // site configuration: every write is admin-only (#2163)
        $this->middleware(['auth', 'can:admin'], ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.entity-types.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['entityTypes.name' => 'asc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_entity_type',
            'internal_entity_type_index',
            action([EntityTypesController::class, 'index']),
            $this->filter,
            EntityType::query()->select('entity_types.*'),
            ['entity_types.name' => 'asc'],
        );

        $entityTypes = $listResultSet->getList()->paginate($listResultSet->getLimit());

        return view('entityTypes.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
            ->with(compact('entityTypes'))
            ->render();
    }

    /**
    * Filter list of entity types
    *
    *
    * @throws \Throwable
    */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
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
        $this->clearList($listParamSessionStore, 'internal_entity_type', $request->get('key') ?? 'internal_entity_type_index', filters: false);

        return redirect()->route('entity-types.index');
    }

    /**
     * Reset the filtering of entities.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): Response {
        $this->clearList($listParamSessionStore, 'internal_entity_type', $request->get('key') ?? 'internal_entity_type_index', filters: true);

        return redirect()->route('entity-types.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create(): View
    {
        return view('entityTypes.create-tw');
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(EntityTypeRequest $request, EntityType $entityType): RedirectResponse
    {

        $entityType->create($request->validated());

        return redirect()->route('entity-types.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(EntityType $entityType): View
    {
        return view('entityTypes.show-tw', compact('entityType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EntityType $entityType): View
    {
        $this->middleware('auth');

        return view('entityTypes.edit-tw', compact('entityType'));
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(EntityType $entityType, EntityTypeRequest $request): RedirectResponse
    {
        $entityType->fill($request->validated())->save();

        return redirect('entity-types');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntityType $entityType): RedirectResponse
    {
        $name = $entityType->name;

        if (!$entityType->deleteIfUnused()) {
            flash()->error('Not deleted', sprintf('The entity type %s is still in use and can\'t be deleted.', $name));

            return redirect('entity-types');
        }

        // add to activity log
        Activity::log($entityType, $this->user, 3);

        flash()->success('Success', sprintf('Your entity type %s has been deleted!', $name));

        return redirect('entity-types');
    }

    protected function getFilterOptions(): array
    {
        return  [
        ];
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['entity_types.name' => 'Name', 'entity_types.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }
}
