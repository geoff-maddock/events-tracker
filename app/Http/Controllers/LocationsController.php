<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use App\Entity;
use App\LocationType;
use App\Location;
use App\Visibility;

class LocationsController extends Controller
{
    protected $rules = [
        'name' => ['required', 'min:3'],
        'city' => ['required', 'min:3'],
        'visibility_id' => ['required'],
        'location_type_id' => ['required'],
    ];

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Entity 		$entity
     * @return Response
     */
    public function index(Entity $entity)
    {
        return view('locations.index', compact('entity'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Entity 		$entity
     * @return Response
     */
    public function create(Entity $entity)
    {
        $locationTypes = ['' => ''] + LocationType::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('locations.create', compact('entity', 'locationTypes', 'visibilities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request 			$request
     * @param  \App\Entity 		$entity
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
     * @param  \App\Entity 		$entity
     * @param  \App\Location  	$location
     * @return Response
     */
    public function show(Entity $entity, Location $location)
    {
        return view('locations.show', compact('entity', 'location'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity 		$entity
     * @param  \App\Location  	$location
     * @return Response
     */
    public function edit(Entity $entity, Location $location)
    {
        $locationTypes = ['' => ''] + LocationType::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('locations.edit', compact('entity', 'location', 'locationTypes', 'visibilities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request 			$request
     * @param  \App\Entity 		$entity
     * @param  \App\Location  	$location
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
     * @param  \App\Entity $entity
     * @param  \App\Location $location
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
