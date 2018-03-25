<?php namespace App\Http\Controllers;

use App\Activity;
use App\Filters\UserFilters;
use App\Group;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UserRequest;
use App\Photo;
use App\Profile;
use App\Tag;
use App\User;
use App\UserStatus;
use App\UserType;
use App\Visibility;
use DB;
use Illuminate\Http\Request;
use Log;
use Mail;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UsersController extends Controller
{
    protected $prefix;
    protected $rpp;
    protected $page;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $hasFilter;
    protected $filters;

    public function __construct (User $user)
    {
        $this->middleware('auth', ['except' => array('index', 'show',)]);
        $this->user = $user;

        // prefix for session storage
        $this->prefix = 'app.threads.';

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
     * Set filters attribute
     *
     * @param Request $request
     * @param array $input
     * @return array
     */
    public function setFilters (Request $request, array $input)
    {
        return $this->setAttribute('filters', $input, $request);
    }

    /**
     * Set user session attribute
     *
     * @param String $attribute
     * @param Mixed $value
     * @param Request $request
     * @return Mixed
     */
    public function setAttribute ($attribute, $value, Request $request)
    {
        return $request->session()->put($this->prefix . $attribute, $value);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $this->filters = $this->getFilters($request);

        $this->hasFilter = count($this->filters);

        // initialize the query
        $query = $this->buildCriteria($request);

        // get the threads
        $users = $query->paginate($this->rpp);


        return view('users.index')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'hasFilter' => $this->hasFilter,
                'filters' => $this->filters,
            ])
            ->with(compact('users'));
    }

    /**
     * Filter the list of users
     *
     * @return Response
     * @throws \Throwable
     */
    public function filter (Request $request)
    {
        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // update filters based on the request input
        $this->setFilters($request, array_merge($this->getFilters($request), $request->input()));

        // get the merged filters
        $this->filters = $this->getFilters($request);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // flag that there are filters
        $this->hasFilter = count($this->filters);

        // get the criteria given the request (could pass filters instead?)
        $query = $this->buildCriteria($request);

        // get the threads
        $users = $query->paginate($this->rpp);

        return view('users.index')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'hasFilter' => $this->hasFilter,
                'filters' => $this->filters,

            ])
            ->with(compact('users'));

    }

    /**
     * Builds the criteria from the session
     *
     * @return $query
     */
    public function buildCriteria (Request $request)
    {

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = $this->user
            ->orderBy('name', 'ASC')
            ->orderBy($this->sortBy, $this->sortOrder);

        // add the criteria from the session
        // check request for passed filter values
        if (!empty($filters['filter_email'])) {
            // getting name from the request
            $name = $filters['filter_email'];
            $query->where('email', 'like', '%' . $name . '%');
        }

        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%' . $name . '%');
        }

        if (!empty($filters['filter_status'])) {
            $status = $filters['filter_status'];
            $query->where('status', '=', $status);

            // add to filters array
            $filters['filter_status'] = $status;
        }

        // change this - should be seperate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

    /**
     * Reset the filtering of users
     *
     * @param Request $request
     * @return Response
     * @throws \Throwable
     */
    public function reset (Request $request)
    {
        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        $hasFilter = 0;

        // default
        $query = User::orderBy('name', 'ASC');

        // paginate
        $users = $query->paginate($this->rpp);

        return view('users.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('users'))
            ->render();

    }


    /**
     * Update the page list parameters from the request
     * @param $request
     */
    protected function updatePaging ($request)
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        };

        // set sort direction
        if ($request->input('sort_direction')) {
            $this->sortOrder = $request->input('sort_direction');
        };

        // set results per page
        if ($request->input('rpp')) {
            $this->rpp = $request->input('rpp');
        };
    }

    /**
     * Get session filters
     *
     * @param Request $request
     * @return Array
     */
    public function getFilters (Request $request)
    {
        return $this->getAttribute('filters', $this->getDefaultFilters(), $request);
    }

    /**
     * Get user session attribute
     *
     * @param String $attribute
     * @param Mixed $default
     * @param Request $request
     * @return Mixed
     */
    public function getAttribute ($attribute, $default = null, Request $request)
    {
        return $request->session()
            ->get($this->prefix . $attribute, $default);
    }

    /**
     * Get the default filters array
     *
     * @return array
     */
    public function getDefaultFilters ()
    {
        return array();
    }

    /**
     * Show a form to create a new user.
     *
     * @return view
     **/

    public function create ()
    {
        $userTypes = ['' => ''] + UserType::pluck('name', 'id');
        $visibilities = ['' => ''] + Visibility::pluck('name', 'id');
        $userStatuses = ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $tags = Tag::pluck('name', 'id');

        return view('users.create');
    }

    public function show (User $user)
    {
        // if there is no profile, create one?

        if (!$user->profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;

            $profile->save();

            return redirect('users/' . $user->id);
        }

        return view('users.show', compact('user'));
    }


    public function store (UserRequest $request, User $user)
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

    public function edit (User $user)
    {
        $this->middleware('auth');

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $userStatuses = ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $groups = Group::orderBy('name')->pluck('name', 'id')->all();

        return view('users.edit', compact('user', 'visibilities', 'userStatuses', 'groups'));
    }

    public function update (User $user, ProfileRequest $request)
    {
        $user->fill($request->input())->save();
        $user->profile->fill($request->input())->save();

        if ($request->has('group_list')) {
            $user->groups()->sync($request->input('group_list', []));
        };

        flash('Success', 'Your user has been updated');

        return view('users.show', compact('user'));
    }


    public function destroy (User $user)
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
    public function addPhoto ($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif'
        ]);

        // attach to user
        $user = User::find($id);

        // make the photo based on the stored file
        $photo = $this->makePhoto($request->file('file'));

        // count existing photos, and if zero, make this primary
        if (count($user->photos) == 0) {
            $photo->is_primary = 1;
        };

        $photo->save();

        // attach to user
        $user->addPhoto($photo);
    }

    /**
     * @param UploadedFile $file
     * @return mixed
     */
    protected function makePhoto (UploadedFile $file)
    {
        return Photo::named($file->getClientOriginalName())
            ->move($file);
    }

    /**
     * Delete a photo
     *
     * @param  int $id
     * @param Request $request
     * @return void
     */
    public function deletePhoto ($id, Request $request)
    {

        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif'
        ]);

        // detach from user
        $user = User::find($id);
        $user->removePhoto($photo);

        $photo = $this->deletePhoto($request->file('file'));
        $photo->save();


    }

    /**
     * Mark user as activated
     *
     * @param $id
     * @param Request $request
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

        flash()->success('Success', 'User ' . $user->name . ' is now activated.');

        return back();

    }
}
