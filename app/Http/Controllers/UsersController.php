<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Requests\ProfileRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Mail;
use App\User;
use App\Profile;
use App\Entity;
use App\UserType;
use App\EntityType;
use App\Visibility;
use App\Tag;
use App\EventResponse;
use App\Photo;

class UsersController extends Controller {

	public function __construct(User $user)
	{
		$this->middleware('auth', ['except' => array('index', 'show',)]);
		$this->user = $user;
		$this->rpp = 5;

		parent::__construct();
	}
	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function index()
	{
		$users = User::orderBy('name','ASC')->get();

		return view('users.index',compact('users'));
	}

	/**
	 * Show a form to create a new Article.
	 *
	 * @return view
	 **/

	public function create()
	{
		$userTypes = [''=>''] + UserType::lists('name', 'id');
		$visibilities = [''=>''] + Visibility::lists('name', 'id');
		$tags = Tag::lists('name','id');

		return view('users.create');
	}

	public function show(User $user)
	{
		// if there is no profile, create one?

		if (!$user->profile)
		{
			$profile = new Profile();
			$profile->user_id = $user->id;
			$profile->save();
		}


		return view('users.show', compact('user'));
	}


	public function store(UserRequest $request, User $user)
	{
		$input = $request->all();

		$user->create($input);

		// if there is no profile, create one
		if (!$user->profile)
		{
			$profile = new Profile();
			$profile->user_id = $user->id;
			$profile->save();
		}

		$user->tags()->attach($request->input('tag_list'));

		\Session::flash('flash_message', 'Your user has been created!');

		//return redirect()->route('users.index');
	}

	public function edit(User $user)
	{
		$this->middleware('auth');

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();
		$tags = Tag::lists('name','id');

		return view('users.edit', compact('user', 'visibilities','tags'));
	}

	public function update(User $user, ProfileRequest $request)
	{
		$user->profile->fill($request->input())->save();

		flash('Success', 'Your user has been updated');

		return view('users.show', compact('user'));
	}

	public function destroy(User $user)
	{
		$user->delete();

		return redirect('users');
	}


	/**
	 * Add a photo to a user
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function addPhoto($id, Request $request)
	{
		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

		$photo = $this->makePhoto($request->file('file'));
		$photo->save();

		// attach to user
		$user = User::find($id);
		$user->addPhoto($photo);
	}
	
	/**
	 * Delete a photo
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function deletePhoto($id, Request $request)
	{

		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

		// detach from user
		$user = User::find($id);
		$user->removePhoto($photo);

		$photo = $this->deletePhoto($request->file('file'));
		$photo->save();


	}

	protected function makePhoto(UploadedFile $file)
	{
		return Photo::named($file->getClientOriginalName())
			->move($file);
	}
}
