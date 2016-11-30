<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use App\Entity;
use App\Link;
use App\Visibility;

class LinksController extends Controller {


	protected $rules = [
		'text' => ['required', 'min:3'],
		'url' => ['required', 'min:3'],
	];

	public function __construct(Entity $entity)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->entity = $entity;

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
		return view('links.index', compact('entity'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @param  \App\Entity 		$entity
	 * @return Response
	 */
	public function create(Entity $entity)
	{

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();

		return view('links.create', compact('entity','visibilities'));
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

		$link = Link::create($input);

		$entity->links()->attach($link->id);


		flash()->success('Success', 'Your link has been created');

		return redirect()->route('entities.show', $entity->id);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Entity 		$entity
	 * @param  \App\Link     	$link
	 * @return Response
	 */
	public function show(Entity $entity, Link $link)
	{
		return view('links.show', compact('entity', 'link'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Entity 		$entity
	 * @param  \App\Link  	        $link
	 * @return Response
	 */
	public function edit(Entity $entity, Link $link)
	{
		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();

		return view('links.edit', compact('entity', 'link', 'visibilities' ));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  Request 		$request
	 * @param  \App\Entity 		$entity
	 * @param  \App\Link     	$link
	 * @return Response
	 */
	public function update(Request $request, Entity $entity, Link $link)
	{
		$msg = '';

		$link->fill($request->input())->save();
 
		flash()->success('Success', 'Your link has been updated!');

		return redirect()->route('entities.show', $entity->id);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Entity 		$entity
	 * @param  \App\Link    	$link
	 * @return Response
	 */
	public function destroy(Entity $entity, Link $link)
	{
		$link->delete();

		\Session::flash('flash_message', 'Your links has been deleted!');

		return redirect()->route('entities.show', $entity->id);

	}

}
