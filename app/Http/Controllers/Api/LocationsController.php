<?php

namespace App\Http\Controllers\Api;

use App\Filters\LocationFilters;
use App\Models\Entity;
use App\Models\Location;
use App\Models\Visibility;
use App\Http\Requests\LocationRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationCollection;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;


class LocationsController extends Controller
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

    protected string $prefix;

    protected LocationFilters $filter;

    protected array $rules = [
        'text' => ['required', 'min:3'],
        'url' => ['required', 'min:3'],
    ];

    public function __construct(LocationFilters $filter)
    {
        // $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'text';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['locations.text' => 'asc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;
        $this->filter = $filter;
        
        // prefix for session storage
        $this->prefix = 'app.locations.';

        parent::__construct();
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(LocationRequest $request): JsonResponse
    {
        $input = $request->all();

        $location = Location::create($input);

        return response()->json($location);
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location): JsonResponse
    {
        return response()->json($location);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Location $location, LocationRequest $request): JsonResponse
    {
        $location->fill($request->input())->save();

        return response()->json($location);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        flash()->success('Success', 'Your location has been deleted!');

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
        // dd('index');
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_location');
        $listParamSessionStore->setKeyPrefix('internal_location_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([LocationsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Location::query()
                    ->leftJoin('entities', 'locations.entity_id', '=', 'entities.id')
                    ->select('locations.*')
        ;

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['locations.id' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // dd the sql
        // dd($query->toSql(), $query->getBindings());

        // get the events
        // @phpstan-ignore-next-line
        $locations = $query->paginate($listResultSet->getLimit());

        // dd($locations);

        return response()->json(new LocationCollection($locations));
    }
}
