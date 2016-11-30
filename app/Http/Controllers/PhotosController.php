<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PhotoRequest;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use App\Photo;
use App\Entity;
use App\PhotoType;
use App\EntityType;
use App\Visibility;
use App\Tag;

class PhotosController extends Controller {

	public function __construct(Photo $photo)
	{
		$this->middleware('auth', ['except' => array('index', 'show')]);
		$this->photo = $photo;
	}
	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function index()
	{
		$photos = Photo::get();

		return view('photos.index', compact('photos'));
	}

	/**
	 * Show a form to create a new Article.
	 *
	 * @return view
	 **/

	public function create()
	{

		$tags = Tag::lists('name','id');
		$entities = Entity::lists('name','id');

		return view('photos.create', compact('venues','photoTypes','visibilities','tags','entities'));
	}

	public function show(Photo $photo)
	{
		return view('photos.show', compact('photo'));
	}


	public function store(PhotoRequest $request, Photo $photo)
	{
		$input = $request->all();

		$photo = $photo->create($input);

		$photo->tags()->attach($request->input('tag_list',[]));
		$photo->entities()->attach($request->input('entity_list'));

		\Session::flash('flash_message', 'Your photo has been created!');

		return redirect()->route('photos.index');
	}

	public function edit(Photo $photo)
	{
		$this->middleware('auth');

		$type = EntityType::where('name', 'Venue')->first();
		$venues = [''=>''] + DB::table('entities')->where('entity_type_id', $type->id)->orderBy('name', 'ASC')->lists('name','id');
		$photoTypes = [''=>''] + PhotoType::lists('name', 'id');
		$visibilities = [''=>''] + Visibility::lists('name', 'id');
		$tags = Tag::orderBy('name','ASC')->lists('name','id');
		$entities = Entity::orderBy('name','ASC')->lists('name','id');

		return view('photos.edit', compact('photo', 'venues', 'photoTypes', 'visibilities','tags','entities'));
	}

	public function update(Photo $photo, PhotoRequest $request)
	{
		$photo->fill($request->input())->save();

		$photo->tags()->sync($request->input('tag_list',[]));
		$photo->entities()->sync($request->input('entity_list',[]));

		\Session::flash('flash_message', 'Your photo has been updated!');

		return redirect('photos');
	}

	public function destroy($id)
	{
		$photo = Photo::findOrFail($id)->delete();

		flash('Success', 'Your photo has been deleted');

		return back();
	}

	public function setPrimary($id)
	{
		$photo = Photo::findOrFail($id);

		$photo->is_primary = 1;
		$photo->save();

		flash('Success', 'The primary photo was updated.');

		return back();
	}

	public function unsetPrimary($id)
	{
		$photo = Photo::findOrFail($id);

		$photo->is_primary = 0;
		$photo->save();

		flash('Success', 'The primary photo was unset.');

		return back();
	}


	protected function saveAs($name) 
	{
		
	}

}
