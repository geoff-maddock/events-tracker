<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LocationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Entity;
use App\Models\LocationType;
use App\Models\Location;
use App\Models\Visibility;

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

    protected $rules = [
        'name' => ['required', 'min:3'],
        'slug' => ['required', 'min:3'],
        'city' => ['required', 'min:3'],
        'visibility_id' => ['required'],
        'location_type_id' => ['required'],
    ];

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->defaultSortCriteria = ['locations.name', 'asc'];

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Entity 		$entity
     * @return Response
     */
    public function index(Entity $entity)
    {
        return view('locations.index', compact('entity'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Entity 		$entity
     * @return Response
     */
    public function create(Entity $entity)
    {
        $locationTypes = LocationType::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('locations.create', compact('entity'))
            ->with($this->getFormOptions());
    }

    protected function getFormOptions(): array
    {
        return [
            'locationTypeOptions' => ['' => ''] + LocationType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all()
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request 			$request
     * @param  Entity 		$entity
     * @return Response
     */
    public function store(Request $request, Entity $entity)
    {
        $msg = '';

        // get the request
        $input = $request->all();
        $input['entity_id'] = $entity->id;

        $this->validate($request, $this->rules);

        $location = Location::create($input);

        flash()->success('Success', 'Your location has been created');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Display the specified resource.
     *
     * @param  Entity 		$entity
     * @param  Location  	$location
     * @return Response
     */
    public function show(Entity $entity, Location $location)
    {
        return view('locations.show', compact('entity', 'location'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Entity 		$entity
     * @param  Location  	$location
     * @return Response
     */
    public function edit(Entity $entity, Location $location)
    {
        $locationTypes = ['' => ''] + LocationType::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('locations.edit', compact('entity', 'location'))->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request 			$request
     * @param  Entity 		$entity
     * @param  Location  	$location
     * @return Response
     */
    public function update(Request $request, Entity $entity, Location $location)
    {
        $msg = '';

        $location->fill($request->input())->save();

        flash()->success('Success', 'Your location has been updated!');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Entity $entity
     * @param  Location $location
     * @return Response
     * @throws \Exception
     */
    public function destroy(Entity $entity, Location $location)
    {
        $location->delete();

        flash()->success('Success', 'Your location has been deleted!');

        return redirect()->route('entities.show', $entity->slug);
    }
}
