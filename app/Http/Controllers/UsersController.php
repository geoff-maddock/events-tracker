<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Requests\ProfileRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use App\User;
use App\Entity;
use App\UserType;
use App\EntityType;
use App\Visibility;
use App\Tag;

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

		// $users = $this->user->orderBy('name', 'ASC')->get();
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

		return view('users.show', compact('user'));
	}


	public function store(UserRequest $request, User $user)
	{
		$input = $request->all();

		$user->create($input);

		// if there is no profile, create one
		if (!$user->profile)
		{
			$profile = new App/Profile();
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
		//$user->fill($request->input())->save();

		//$user->tags()->sync($request->input('tag_list',[]));


		flash('Success', 'Your user has been updated');

		return view('users.show', compact('user'));
	}

	public function destroy(User $user)
	{
		$user->delete();

		return redirect('users');
	}

}
