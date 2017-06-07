<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use App\Entity;
use App\Contact;
use App\Visibility;

class ContactsController extends Controller {


	protected $rules = [
		'name' => ['required', 'min:3'],
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
		return view('contacts.index', compact('entity'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @param  \App\Entity 		$entity
	 * @return Response
	 */
	public function create(Entity $entity)
	{

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

		return view('contacts.create', compact('entity','visibilities'));
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

		$contact = Contact::create($input);

		$entity->contacts()->attach($contact->id);

		flash()->success('Success', 'Your contact has been created');

		return redirect()->route('entities.show', $entity->id);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Entity 		$entity
	 * @param  \App\Contact  	$contact
	 * @return Response
	 */
	public function show(Entity $entity, Contact $contact)
	{
		return view('contacts.show', compact('entity', 'contact'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Entity 		$entity
	 * @param  \App\Contact  	$contact
	 * @return Response
	 */
	public function edit(Entity $entity, Contact $contact)
	{

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

		return view('contacts.edit', compact('entity', 'contact', 'visibilities' ));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  Request 			$request
	 * @param  \App\Entity 		$entity
	 * @param  \App\Contact  	$contact
	 * @return Response
	 */
	public function update(Request $request, Entity $entity, Contact $contact)
	{
		$msg = '';

		$contact->fill($request->input())->save();
 
		flash()->success('Success', 'Your contact has been updated!');

		return redirect()->route('entities.show', $entity->id);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Entity 		$entity
	 * @param  \App\Contact  	$contact
	 * @return Response
	 */
	public function destroy(Entity $entity, Contact $contact)
	{
		$contact->delete();

		\Session::flash('flash_message', 'Your contacts has been deleted!');

		return redirect()->route('entities.show', $entity->id);

	}

}
