<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Requests\ProfileRequest;
use App\UserStatus;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Mail;
use App\User;
use App\Profile;
use App\UserType;
use App\Visibility;
use App\Tag;
use App\Photo;
use App\Activity;

class UsersController extends Controller {

	public function __construct(User $user)
	{
		$this->middleware('auth', ['except' => array('index', 'show',)]);
		$this->user = $user;
		$this->rpp = 25;
        $this->page = 1;
        $this->sort = array('name', 'desc');
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = NULL;
        $this->hasFilter = 0;

        parent::__construct();
	}
	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function index()
	{
		$users = User::orderBy('name','ASC')->paginate($this->rpp);

		return view('users.index',compact('users'));
	}

	/**
	 * Show a form to create a new user.
	 *
	 * @return view
	 **/

	public function create()
	{
		$userTypes = [''=>''] + UserType::pluck('name', 'id');
		$visibilities = [''=>''] + Visibility::pluck('name', 'id');
        $userStatuses = [''=>''] + UserStatus::orderBy('name','ASC')->pluck('name', 'id')->all();
		$tags = Tag::pluck('name','id');

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

			return redirect('users/'.$user->id);
		}

		return view('users.show', compact('user'));
	}


	public function store(UserRequest $request, User $user)
	{
		$input = $request->all();

		$user->create($input);
		$user->user_status_id = 1;

		// if there is no profile, create one
		$profile = new Profile();
		$profile->user_id = $user->id;
		$profile->save();

		$user->tags()->attach($request->input('tag_list'));

		flash('Success', 'Your user has been created!');

	}

	public function edit(User $user)
	{
		$this->middleware('auth');

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();
        $userStatuses = [''=>''] + UserStatus::orderBy('name','ASC')->pluck('name', 'id')->all();
		$tags = Tag::pluck('name','id');

		return view('users.edit', compact('user', 'visibilities','userStatuses','tags'));
	}

	public function update(User $user, ProfileRequest $request)
	{
	    $user->fill($request->input())->save();
		$user->profile->fill($request->input())->save();

        $user->groups()->sync($request->input('group_list', []));

		flash('Success', 'Your user has been updated');

		return view('users.show', compact('user'));
	}


	public function destroy(User $user)
	{
		Activity::log($user, $this->user, 3);

		$user->delete();

		return redirect('users');
	}


    /**
     * Add a photo to a user
     *
     * @param  int $id
     * @param Request $request
     * @return void
     */
	public function addPhoto($id, Request $request)
	{
		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

        // attach to user
        $user = User::find($id);

        // make the photo based on the stored file
		$photo = $this->makePhoto($request->file('file'));

        // count existing photos, and if zero, make this primary
        if (count($user->photos) == 0)
        {
            $photo->is_primary=1;
        };

		$photo->save();

        // attach to user
		$user->addPhoto($photo);
	}

    /**
     * Delete a photo
     *
     * @param  int $id
     * @param Request $request
     * @return void
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

    /**
     * @param UploadedFile $file
     * @return mixed
     */
    protected function makePhoto(UploadedFile $file)
	{
		return Photo::named($file->getClientOriginalName())
			->move($file);
	}


    /**
     * Mark user as activated
     *
     * @return Response
     */
    public function activate ($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');
            return back();
        };

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');
            return back();
        };

        // add the following response
        $user->user_status_id = 2;
        $user->save();


        Log::info('User ' . $user->name . ' is activated.');

        flash()->success('Success', 'User '.$user->name .' is now activated.');

        return back();

    }
}
