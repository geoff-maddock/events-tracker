<?php

namespace App\Http\Controllers\Api;

use App\Filters\LinkFilters;
use App\Models\Entity;
use App\Models\Link;
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
        $this->middleware('auth', ['only' => ['store', 'update']]);

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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, $this->rules + ['entity_id' => ['required', 'integer', 'exists:entities,id']]);

        // links belong to entities, so creating one needs edit rights on that entity
        $entity = Entity::findOrFail($request->input('entity_id'));
        if (!$this->user || $this->user->cannot('update', $entity)) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $input = $request->only(['text', 'url', 'title']);
        $input['is_primary'] = $request->boolean('is_primary') ? 1 : 0;

        $link = Link::create($input);
        $entity->links()->attach($link->id);

        return response()->json($link, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity, Link $link): View
    {
        return view('links.show', compact('entity', 'link'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Link $link): JsonResponse
    {
        if (!$this->user || $this->user->cannot('update', $link)) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $this->validate($request, $this->rules);

        $input = $request->only(['text', 'url', 'title']);
        $input['is_primary'] = $request->boolean('is_primary') ? 1 : 0;

        $link->fill($input)->save();

        return response()->json($link);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Link $link): JsonResponse
    {
        if (!$this->user || $this->user->cannot('delete', $link)) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $link->delete();

        return response()->json([], 204);
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
