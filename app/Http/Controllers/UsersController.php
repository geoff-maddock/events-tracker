<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Group;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UserRequest;
use App\Photo;
use App\Profile;
use App\Tag;
use App\User;
use App\UserStatus;
use App\Visibility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;
use Mail;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UsersController extends Controller
{
    protected $prefix;
    protected $rpp;
    protected $page;
    protected $defaultRpp;
    protected $defaultSortBy;
    protected $defaultSortOrder;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $hasFilter;
    protected $filters;
    protected $tabs;

    public function __construct(User $user)
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
        $this->user = $user;

        // prefix for session storage
        $this->prefix = 'app.threads.';

        $this->rpp = 25;
        $this->page = 1;
        $this->sort = ['name', 'asc'];
        $this->sortBy = 'name';
        $this->sortOrder = 'asc';
        // default list variables
        $this->defaultRpp = 25;
        $this->defaultSortBy = 'name';
        $this->defaultSortOrder = 'asc';
        $this->defaultCriteria = null;
        $this->hasFilter = 0;

        parent::__construct();
    }

    /**
     * Set filters attribute.
     *
     * @return array
     */
    public function setFilters(Request $request, array $input)
    {
        return $this->setAttribute('filters', $input, $request);
    }

    /**
     * Set tabs attribute.
     *
     * @return array
     */
    public function setTabs(Request $request, array $input)
    {
        return $this->setAttribute('tabs', $input, $request);
    }

    /**
     * Set user session attribute.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return void
     */
    public function setAttribute($attribute, $value, Request $request)
    {
        $request->session()->put($this->prefix.$attribute, $value);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response | string
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
     * Checks if there is a valid filter.
     *
     * @param $filters
     */
    public function hasFilter($filters): bool
    {
        $arr = $filters;
        unset($arr['rpp'], $arr['sortOrder'], $arr['sortBy'], $arr['page']);

        return count(array_filter($arr, function ($x) { return !empty($x); }));
    }

    /**
     * Filter the list of users.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function filter(Request $request)
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($this->filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

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
     * Builds the criteria from the session.
     *
     * @return $query
     */
    public function buildCriteria(Request $request)
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = User::orderBy($this->sortBy, $this->sortOrder);

        // add the criteria from the session
        // check request for passed filter values
        if (!empty($filters['filter_email'])) {
            // getting name from the request
            $name = $filters['filter_email'];
            $query->where('email', 'like', '%'.$name.'%');
        }

        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%'.$name.'%');
        }

        if (!empty($filters['filter_status'])) {
            $status = $filters['filter_status'];
            $query->where('user_status_id', '=', $status);

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
     * Reset the filtering of users.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function reset(Request $request)
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
     * Update the page list parameters from the request.
     *
     * @param $request
     */
    protected function updatePaging($request)
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        }

        // set sort direction
        if ($request->input('sort_direction')) {
            $this->sortOrder = $request->input('sort_direction');
        }

        // set results per page
        if ($request->input('rpp')) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Get session filters.
     *
     * @return array
     */
    public function getFilters(Request $request)
    {
        return $this->getAttribute('filters', $this->getDefaultFilters(), $request);
    }

    /**
     * Get session tabs.
     *
     * @return array
     */
    public function getTabs(Request $request)
    {
        return $this->getAttribute('tabs', $this->getDefaultTabs(), $request);
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getAttribute($attribute, $default = null, Request $request)
    {
        return $request->session()
            ->get($this->prefix.$attribute, $default);
    }

    /**
     * Get the default filters array.
     *
     * @return array
     */
    public function getDefaultFilters()
    {
        return [];
    }

    /**
     * Get the default tags array.
     *
     * @return array
     */
    public function getDefaultTabs()
    {
        return [0 => 'created', 1 => 'tags'];
    }

    /**
     * Show a form to create a new user.
     *
     * @return view
     **/
    public function create()
    {
        $visibilities = ['' => ''] + Visibility::pluck('name', 'id');
        $userStatuses = ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $tags = Tag::pluck('name', 'id');

        return view('users.create');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function show(User $user, Request $request)
    {
        //$this->setTabs($request, $this->getDefaultTabs());

        // get all the tabs from the session
        $this->tabs = $this->getTabs($request);

        // update tabs based on the request input
        $this->setTabs($request, array_replace($this->getTabs($request), $request->input('tabs') ? $request->input('tabs') : []));

        // get the merged tabs
        $this->tabs = $this->getTabs($request);

        $tabs = $this->tabs;

        // if there is no profile, create one?
        if (!$user->profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;

            $profile->save();

            return redirect('users/'.$user->id);
        }

        return view('users.show', compact('user', 'tabs'));
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

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(User $user)
    {
        $this->middleware('auth');

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $userStatuses = ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $groups = Group::orderBy('name')->pluck('name', 'id')->all();

        return view('users.edit', compact('user', 'visibilities', 'userStatuses', 'groups'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update(User $user, ProfileRequest $request)
    {
        $user->fill($request->input())->save();
        $user->profile->fill($request->input())->save();

        if ($request->has('group_list')) {
            $user->groups()->sync($request->input('group_list', []));
        }

        // get all the tabs from the session
        $tabs = $this->getTabs($request);

        // add to activity log
        Activity::log($user, $this->user, 2);

        flash('Success', 'Your user has been updated');

        return view('users.show', compact('user', 'tabs'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws \Exception
     */
    public function destroy(User $user)
    {
        // add to activity log
        Activity::log($user, $this->user, 3);

        $user->delete();

        return redirect('users');
    }

    /**
     * Add a photo to a user.
     *
     * @param int $id
     *
     * @return void
     */
    public function addPhoto($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        // attach to user
        $user = User::find($id);

        // make the photo based on the stored file
        $photo = $this->makePhoto($request->file('file'));

        // count existing photos, and if zero, make this primary
        if (0 == count($user->photos)) {
            $photo->is_primary = 1;
        }

        $photo->save();

        // attach to user
        $user->addPhoto($photo);
    }

    /**
     * @return mixed
     */
    protected function makePhoto(UploadedFile $file)
    {
        return Photo::named($file->getClientOriginalName())
            ->move($file);
    }

    /**
     * Delete a photo.
     *
     * @param int $id
     *
     * @return void
     */
    public function deletePhoto($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        // detach from user
        $user = User::find($id);

        $photo = $this->deletePhoto($request->file('file'));
        $photo->save();
    }

    /**
     * Mark user as activated.
     *
     * @param $id
     *
     * @return Response
     */
    public function activate($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        // add the following response
        $user->user_status_id = 2;
        $user->save();

        Log::info('User '.$user->name.' is activated.');

        // add to activity log
        Activity::log($user, $this->user, 10);

        flash()->success('Success', 'User '.$user->name.' is now activated.');

        // email the user
        $this->notifyUserActivated($user);

        return back();
    }

    /**
     * Mark user as suspended.
     *
     * @param $id
     *
     * @return Response
     */
    public function suspend($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        // add the following response
        $user->user_status_id = 3;
        $user->save();

        // add to activity log
        Activity::log($user, $this->user, 11);

        Log::info('User '.$user->name.' is suspended.');

        flash()->success('Success', 'User '.$user->name.' is now suspended.');

        return back();
    }

    /**
     * Send a site update reminder to the user.
     *
     * @param $id
     *
     * @return Response
     */
    public function reminder($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        // email the user
        $this->notifyUser($user);

        // add to activity log
        Activity::log($user, $this->user, 12);

        Log::info('User '.$user->name.' was sent a reminder');

        flash()->success('Success', 'A reminder email was sent to  '.$user->name.' at '.$user->email);

        return back();
    }

    /**
     * Send a weekly site update reminder to the user.
     *
     * @param $id
     *
     * @return Response
     */
    public function weekly($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        // email the user
        $this->notifyUserWeekly($user);

        // add to activity log
        Activity::log($user, $this->user, 12);

        Log::info('User '.$user->name.' was sent a reminder');

        flash()->success('Success', 'A reminder email was sent to  '.$user->name.' at '.$user->email);

        return back();
    }

    /**
     * Mark user as deleted.
     *
     * @param $id
     *
     * @return Response
     */
    public function delete($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        // add the following response
        $user->user_status_id = 5;
        $user->save();

        Log::info('User '.$user->name.' is deleted.');

        flash()->success('Success', 'User '.$user->name.' is now deleted.');

        // add to activity log
        Activity::log($user, $this->user, 3);

        $user->delete();

        return back();
    }

    /**
     * @param $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyUser($user)
    {
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        $events = [];

        // build an array of events that are in the future based on what the user follows
        if ($entities = $user->getEntitiesFollowing()) {
            foreach ($entities as $entity) {
                if (count($entity->futureEvents()) > 0) {
                    $events[$entity->name] = $entity->futureEvents();
                }
            }
        }
        // build an array of future events based on tags the user follows
        if ($tags = $user->getTagsFollowing()) {
            foreach ($tags as $tag) {
                if (count($tag->futureEvents()) > 0) {
                    $events[$tag->name] = $tag->futureEvents();
                }
            }
        }

        Mail::send('emails.user-reminder', ['user' => $user, 'admin_email' => $admin_email, 'site' => $site, 'url' => $url, 'events' => $events], function ($m) use ($user,  $admin_email, $site) {
            $m->from($admin_email, $site);
            $m->to($user->email, $user->name)
                ->bcc($admin_email)
                ->subject($site.': Site updates for '.$user->name.' :: '.Carbon::now()->format('D F jS'));
        });

        return back();
    }

    /**
     * @param $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyUserActivated($user)
    {
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        Mail::send('emails.user-activated', ['user' => $user, 'admin_email' => $admin_email, 'site' => $site, 'url' => $url], function ($m) use ($user,  $admin_email, $site) {
            $m->from($admin_email, $site);
            $m->to($user->email, $user->name)
                ->bcc($admin_email)
                ->subject($site.': Account activated for '.$user->name.' :: '.Carbon::now()->format('D F jS'));
        });

        return back();
    }

    /**
     * @param $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyUserWeekly($user)
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        $show_count = 100;
        $events = [];
        $interests = [];

        // build an array of events that are in the future based on what the user follows
        if ($entities = $user->getEntitiesFollowing()) {
            foreach ($entities as $entity) {
                if (count($entity->todaysEvents()) > 0) {
                    $interests[$entity->name] = $entity->futureEvents();
                }
            }
        }
        // build an array of future events based on tags the user follows
        if ($tags = $user->getTagsFollowing()) {
            foreach ($tags as $tag) {
                if (count($tag->futureEvents()) > 0) {
                    $interests[$tag->name] = $tag->futureEvents();
                }
            }
        }

        // get the next x events they are attending
        $events = $user->getAttendingFuture()->take($show_count);

        // if there are more than 0 events
        if ((null !== $events && $events->count() > 0) || (null !== $interests && count($interests) > 0)) {
            // send an email containing that list
            Mail::send('emails.weekly-events',
                ['user' => $user, 'interests' => $interests, 'events' => $events, 'url' => $url, 'site' => $site],
                function ($m) use ($user, $admin_email, $reply_email, $site) {
                    $m->from($reply_email, $site);

                    $dt = Carbon::now();
                    $m->to($user->email, $user->name)
                        ->bcc($admin_email)
                        ->subject($site.': Weekly Reminder - '.$dt->format('l F jS Y'));
                });
        }

        return back();
    }

    /**
     * Update the page list parameters from the request.
     *
     * @param $filters
     */
    protected function getPaging($filters): void
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        $this->rpp = $filters['rpp'] ?? $this->rpp;
    }
}
