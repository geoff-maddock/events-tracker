<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntityTypeRequest;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\EntityType;

class EntityTypesController extends Controller {

	public function __construct(EntityType $entityType)
	{
		$this->middleware('auth', ['except' => array('index', 'show')]);
		$this->entityType = $entityType;
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$entities = $this->entity->orderBy('created_at', 'ASC')->get();

		return view('entities.index', compact('entities'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('entities.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	 public function store(EntityRequest $request, Entity $entity)
 	{
 		$input = $request->all();

 		$entity->create($input);

 		return redirect()->route('entities.index');
 	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show(Entity $entity)
	{
		return view('entities.show', compact('entity'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit(Entity $entity)
	{
		$this->middleware('auth');

		$entityTypes = App/EntityType::pluck('name', 'id');

		return view('entities.edit', compact('entity','entityTypes'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Entity $entity, Request $request)
	{
		$entity->fill($request->input())->save();

		return redirect('entities');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy(Entity $entity)
	{
		$entity->delete();

		return redirect('entities');
	}

}
