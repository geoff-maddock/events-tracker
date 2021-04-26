<?php

namespace App\Http\Controllers;

use App\Filters\UserFilters;
use App\Models\Activity;
use App\Models\Group;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UserRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public const DEFAULT_SHOW_COUNT = 100;

    protected string $prefix;

    protected int $page;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    // array of sort criteria to be applied in order
    protected array $defaultSortCriteria;

    protected bool $hasFilter;

    protected $filters;

    protected $tabs;

    protected array $defaultTabs;

    protected UserFilters $filter;

    public function __construct(UserFilters $filter)
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.users.';

        // default list variables - move to function that set from session or default
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultLimit = 25;

        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['name' => 'desc'];

        // tabs
        $this->defaultTabs = ['events' => 'created', 'following' => 'tags'];

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_user');
        $listParamSessionStore->setKeyPrefix('internal_user_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([UsersController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = User::query()->leftJoin('user_statuses', 'users.user_status_id', '=', 'user_statuses.id')->select('users.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['users.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the users
        $users = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('users.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters()
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('users'))
            ->render();
    }

    /**
     * Filter the list of users.
     *
     * @throws Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_user');
        $listParamSessionStore->setKeyPrefix('internal_user_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([UsersController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = User::query()->leftJoin('user_statuses', 'users.user_status_id', '=', 'user_statuses.id')->select('users.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['users.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the users
        $users = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('users.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters()
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('users'))
            ->render();
    }

    /**
     * Reset the limit, sort, order
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the limit, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_user_index';
        $listParamSessionStore->setBaseIndex('internal_user');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('users.index');
    }

    /**
     * Reset the filtering of useres.
     *
     * @throws \Throwable
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_user_index';
        $listParamSessionStore->setBaseIndex('internal_user');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('users.index');
    }

    /**
     * Get the default filters array.
     *
     * @return array
     */
    public function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * Get the default tags array.
     *
     * @return array
     */
    public function getDefaultTabs(): array
    {
        return ['events' => 'created', 'following' => 'tags'];
    }

    /**
     * Show a form to create a new user.
     *
     * @return View
     **/
    public function create(): View
    {
        return view('users.create')->with($this->getFormOptions());
    }

    public function setTabs(Request $request): void
    {
        if (null !== $request->input('tabs')) {
            $request->session()->put($this->prefix . 'tabs', $request->input('tabs'));
        }
    }

    public function getTabs(Request $request): array
    {
        return $request->session()->get($this->prefix . 'tabs', $this->getDefaultTabs());
    }

    public function show(User $user, Request $request)
    {
        // if tabs were passed in the request, store them
        $this->setTabs($request);

        // get the current tabs from the session
        $tabs = $this->getTabs($request);

        // if there is no profile, create one?
        if (!$user->profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;

            $profile->save();

            return redirect('users/' . $user->id);
        }

        return view('users.show', compact('user', 'tabs'));
    }

    public function store(UserRequest $request, User $user): void
    {
        $input = $request->all();

        $user->create($input);
        $user->user_status_id = 1;

        // if there is no profile, create one
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();

        flash('Success', 'Your user has been created!');
    }

    public function edit(User $user): View
    {
        $this->middleware('auth');

        return view('users.edit', compact('user'))
            ->with($this->getFormOptions());
    }

    public function update(User $user, ProfileRequest $request): View
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
     * @return RedirectResponse|Redirector
     *
     * @throws Exception
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
     * @throws ValidationException
     */
    public function addPhoto(int $id, Request $request): void
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        $fileName = time() . '_' . $request->file->getClientOriginalName();
        $filePath = $request->file('file')->storeAs('photos', $fileName, 'public');

        // attach to user
        if ($user = User::find($id)) {
            // make the photo based on the stored file
            $photo = $this->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if ($user->photos && 0 === count($user->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to user
            $user->addPhoto($photo);
        }
    }

    /**
     * @return mixed
     */
    protected function makePhoto(UploadedFile $file)
    {
        return Photo::named($file->getClientOriginalName())
            ->makeThumbnail();
    }

    /**
     * Mark user as activated.
     * @return Response | RedirectResponse
     */
    public function activate(int $id, Request $request)
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

        Log::info('User ' . $user->name . ' is activated.');

        // add to activity log
        Activity::log($user, $this->user, 10);

        flash()->success('Success', 'User ' . $user->name . ' is now activated.');

        // email the user
        $this->notifyUserActivated($user);

        return back();
    }

    /**
     * Mark user as suspended.

     * @return Response | RedirectResponse
     */
    public function suspend(int $id, Request $request)
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

        Log::info('User ' . $user->name . ' is suspended.');

        flash()->success('Success', 'User ' . $user->name . ' is now suspended.');

        return back();
    }

    /**
     * Send a site update reminder to the user.
     * @return Response | RedirectResponse
     */
    public function reminder(int $id, Request $request)
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

        Log::info('User ' . $user->name . ' was sent a reminder');

        flash()->success('Success', 'A reminder email was sent to  ' . $user->name . ' at ' . $user->email);

        return back();
    }

    /**
     * Send a weekly site update reminder to the user.
     */
    public function weekly(int $id, Request $request)
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

        Log::info('User ' . $user->name . ' was sent a reminder');

        flash()->success('Success', 'A reminder email was sent to  ' . $user->name . ' at ' . $user->email);

        return back();
    }

    /**
     * Mark user as deleted.
     * @return Response | RedirectResponse
     */
    public function delete(int $id, Request $request)
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

        Log::info('User ' . $user->name . ' is deleted.');

        flash()->success('Success', 'User ' . $user->name . ' is now deleted.');

        // add to activity log
        Activity::log($user, $this->user, 3);

        $user->delete();

        return back();
    }

    /**
     * Return the users events in iCal format.
     * @return Response | RedirectResponse
     */
    public function ical(int $id, Request $request)
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
        define('ICAL_FORMAT', 'Ymd\THis\Z');

        // create a calendar object
        $vCalendar = new Calendar($this->user->getFullNameAttribute() . ' Calendar');

        // add the following response
        // get the next x events they are attending
        $events = $user->getAttendingFuture()->take(self::DEFAULT_SHOW_COUNT);

        // loop over events
        foreach ($events as $event) {
            $venue = $event->venue ? $event->venue->name : '';

            $vEvent = new Event();
            $vEvent
                ->setDtStart($event->start_at)
                ->setDtEnd($event->end_at)
                ->setDtStamp($event->created_at)
                ->setSummary($event->name)
                ->setDescription($event->description)
                ->setUniqueId($event->id)
                ->setLocation($venue)
                ->setModified($event->updated_at)
                ->setStatus('CONFIRMED')
                ->setUrl($event->primary_link);

            $vCalendar->addComponent($vEvent);
        }

        // Set the headers
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');

        return $vCalendar->render();
    }

    /**
     * @return RedirectResponse | Response
     */
    protected function notifyUser(User $user)
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

        Mail::send('emails.user-reminder', ['user' => $user, 'admin_email' => $admin_email, 'site' => $site, 'url' => $url, 'events' => $events], function ($m) use ($user, $admin_email, $site) {
            $m->from($admin_email, $site);
            $m->to($user->email, $user->name)
                ->bcc($admin_email)
                ->subject($site . ': Site updates for ' . $user->name . ' :: ' . Carbon::now()->format('D F jS'));
        });

        return back();
    }

    protected function notifyUserActivated(User $user): RedirectResponse
    {
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        Mail::send('emails.user-activated', ['user' => $user, 'admin_email' => $admin_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $admin_email, $site) {
            $m->from($admin_email, $site);
            $m->to($user->email, $user->name)
                ->bcc($admin_email)
                ->subject($site . ': Account activated for ' . $user->name . ' :: ' . Carbon::now()->format('D F jS'));
        });

        return back();
    }

    protected function notifyUserWeekly(User $user): RedirectResponse
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        $show_count = self::DEFAULT_SHOW_COUNT;

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
            Mail::send(
                'emails.weekly-events',
                ['user' => $user, 'interests' => $interests, 'events' => $events, 'url' => $url, 'site' => $site],
                function ($m) use ($user, $admin_email, $reply_email, $site) {
                    $m->from($reply_email, $site);

                    $dt = Carbon::now();
                    $m->to($user->email, $user->name)
                        ->bcc($admin_email)
                        ->subject($site . ': Weekly Reminder - ' . $dt->format('l F jS Y'));
                }
            );
        }

        return back();
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['users.name' => 'Name', 'user_statuses.name' => 'Status', 'users.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }

    protected function getFilterOptions(): array
    {
        return  [
            'userStatusOptions' => ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'name')->all()
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'userStatusOptions' => ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'groupOptions' => Group::orderBy('name')->pluck('name', 'id')->all(),
        ];
    }
}
