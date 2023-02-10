<?php

namespace App\Http\Controllers\Api;

use App\Filters\LinkFilters;
use App\Models\Entity;
use App\Models\Link;
use App\Models\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Http\Resources\LinkCollection;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;

class LinksController extends Controller
{
    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected bool $hasFilter;

    protected LinkFilters $filter;

    protected array $rules = [
        'text' => ['required', 'min:3'],
        'url' => ['required', 'min:3'],
    ];

    public function __construct(LinkFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'text';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['links.text' => 'asc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;
        $this->filter = $filter;

        parent::__construct();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Entity $entity): View
    {
        return view('links.create', compact('entity'))
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Entity $entity): RedirectResponse
    {
        $msg = '';

        // get the request
        $input = $request->all();
        $input['entity_id'] = $entity->id;
        $input['is_primary'] = isset($input['is_primary']) ? 1 : 0;

        $this->validate($request, $this->rules);

        $link = Link::create($input);

        $entity->links()->attach($link->id);

        flash()->success('Success', 'Your link has been created');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity, Link $link): View
    {
        return view('links.show', compact('entity', 'link'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity, Link $link): View
    {
        return view('links.edit', compact('entity', 'link'))
            ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entity $entity, Link $link): RedirectResponse
    {
        $input = $request->all();
        $input['is_primary'] = isset($input['is_primary']) ? 1 : 0;

        $link->fill($input)->save();

        flash()->success('Success', 'Your link has been updated!');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity, Link $link): RedirectResponse
    {
        $link->delete();

        flash()->success('Success', 'Your link has been deleted!');

        return redirect()->route('entities.show', $entity->slug);
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilities' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
        ];
    }

        /**
     * Display a listing of the resource.
     *
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_link');
        $listParamSessionStore->setKeyPrefix('internal_link_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([LinksController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Link::query()
                    ->select('links.*')
        ;

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['links.id' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        // @phpstan-ignore-next-line
        $events = $query->paginate($listResultSet->getLimit());

        return response()->json(new LinkCollection($events));
    }
}
