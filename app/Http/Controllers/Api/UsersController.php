<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\UserFilters;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Filters\EventFilters;
use App\Mail\UserActivation;
use App\Mail\UserSuspended;
use App\Mail\UserUpdate;
use App\Mail\WeeklyUpdate;
use App\Http\Resources\EventCollection;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\ImageHandler;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

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

    protected array $filters;

    protected array $tabs;

    protected array $defaultTabs;

    protected UserFilters $filter;

    public function __construct(UserFilters $filter)
    {
        // $this->middleware('verified', ['except' => ['index', 'show']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.users.';

        // default list variables - move to function that set from session or default
        $this->defaultSort = 'users.created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultLimit = 25;

        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['users.created_at' => 'desc'];

        // tabs
        $this->defaultTabs = ['events' => 'created', 'following' => 'tags'];

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ):  JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_user');
        $listParamSessionStore->setKeyPrefix('internal_user_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([UsersController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only user entities are returned
        $baseQuery = User::query()
        ->leftJoin('user_statuses', 'users.user_status_id', '=', 'user_statuses.id')
        ->select('users.*')
        ->addSelect(['last_active' => Activity::select('created_at')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
        ])
        ->withCasts(['last_active' => 'datetime']);

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the users
        $users = $query
            ->paginate($listResultSet->getLimit());

        return response()->json(new UserCollection($users));
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
        $baseQuery = User::query()
        ->leftJoin('user_statuses', 'users.user_status_id', '=', 'user_statuses.id')
        ->select('users.*')
        ->addSelect(['last_active' => Activity::select('created_at')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
        ])
        ->withCasts(['last_active' => 'created_at']);

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
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('users'))
            ->render();
    }

    /**
     * Reset the limit, sort, order.
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
     */
    public function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * Get the default tags array.
     */
    public function getDefaultTabs(): array
    {
        return ['events' => 'created', 'following' => 'tags'];
    }

    /**
     * Show a form to create a new user.
     *
     **/
    public function create(): View
    {
        return view('users.create')->with($this->getFormOptions());
    }

    public function setTabs(Request $request): void
    {
        if (null !== $request->input('tabs')) {
            $request->session()->put($this->prefix.'tabs', $request->input('tabs'));
        }
    }

    public function getTabs(Request $request): array
    {
        return $request->session()->get($this->prefix.'tabs', $this->getDefaultTabs());
    }

    public function show(User $user, Request $request): JsonResponse
    {
        return response()->json(new UserResource($user));
    }

    public function profile(User $user, Request $request): RedirectResponse
    {
        $this->middleware('auth');

        return redirect('users/'.$this->user->id);
    }

    public function store(UserRequest $request, User $user): JsonResponse
    {   
        // input does have values
        $input = $request->all();

        // changed this to use the static method and it worked
        $user = User::create($input);

        // set the user status
        $user->user_status_id = 1;

        // if there is no profile, create one
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();

        // add to activity log
        Activity::log($user, $this->user, 1);

        return response()->json(new UserResource($user));
    }


    public function update(User $user, ProfileRequest $request): JsonResponse
    {
        $input = $request->all();

        $input['setting_weekly_update'] = isset($input['setting_weekly_update']) ? 1 : 0;
        $input['setting_daily_update'] = isset($input['setting_daily_update']) ? 1 : 0;
        $input['setting_instant_update'] = isset($input['setting_instant_update']) ? 1 : 0;
        $input['setting_forum_update'] = isset($input['setting_forum_update']) ? 1 : 0;
        $input['setting_public_profile'] = isset($input['setting_public_profile']) ? 1 : 0;

        $user->fill($input)->save();
        $user->profile->fill($input)->save();

        if ($request->has('group_list')) {
            $user->groups()->sync($request->input('group_list', []));
        }

        // add to activity log
        Activity::log($user, $this->user, 2);

        return response()->json(new UserResource($user));
    }

    /**
     * @throws Exception
     */
    public function destroy(User $user): JsonResponse
    {
        // add to activity log
        Activity::log($user, $this->user, 3);

        $user->delete();

        return response()->json([], 204);
    }

    /**
     * Add a photo to a user.
     *
     * @throws ValidationException
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): void
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp',
        ]);

        $fileName = time().'_'.$request->file->getClientOriginalName();
        $filePath = $request->file('file')->storePubliclyAs('photos', $fileName, 'external');

        // attach to user
        if ($user = User::find($id)) {
            // make the photo based on the stored file
            $photo = $imageHandler->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if (isset($user->photos) && 0 === count($user->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to user
            $user->addPhoto($photo);
        }
    }

    protected function makePhoto(UploadedFile $file): ?Photo
    {
        return Photo::named($file->getClientOriginalName())
            ->makeThumbnail();
    }

    /**
     * Mark user as activated.
     */
    public function activate(int $id, Request $request): JsonResponse
    {
        // // check if there is a logged in user
        // if (!$this->user) {
        //     flash()->error('Error', 'No user is logged in.');

        //     return back();
        // }

        // Get the user by id
        if (!$user = User::find($id)) {
            return response()->json([]);
        }

        // add the following response
        $user->user_status_id = UserStatus::ACTIVE;
        $user->email_verified_at = Carbon::now();
        $user->save();

        Log::info('User '.$user->name.' is activated.');

        // add to activity log
        Activity::log($user, $this->user, 10);

        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        Mail::to($user->email)->send(new UserActivation($url, $site, $admin_email, $reply_email, $user));

        return response()->json(new UserResource($user));
    }

    /**
     * Mark user as suspended.
     */
    public function suspend(int $id, Request $request): Response | RedirectResponse
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

        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        Mail::to($user->email)->send(new UserSuspended($url, $site, $admin_email, $reply_email, $user));

        return back();
    }

    /**
     * Send a site update reminder to the user.
     */
    public function reminder(int $id, Request $request): RedirectResponse
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
     */
    public function weekly(int $id, Request $request): RedirectResponse
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

        // if the user does not have this setting, continue
        if ($user->profile->setting_weekly_update !== 1) {
            flash()->error('Error', 'User has weekly updates disabled');

            return back();
        }

        // email the user
        $this->notifyUserWeekly($user);

        // add to activity log
        Activity::log($user, $this->user, 12);

        Log::info('User '.$user->name.' was sent a weekly reminder');

        flash()->success('Success', 'A weekly reminder email was sent to  '.$user->name.' at '.$user->email);

        return back();
    }

    /**
     * Mark user as deleted.
     *
     * @return Response|RedirectResponse
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

        Log::info('User '.$user->name.' is deleted.');

        flash()->success('Success', 'User '.$user->name.' is now deleted.');

        // add to activity log
        Activity::log($user, $this->user, 3);

        $user->delete();

        return back();
    }


    /**
     * @return RedirectResponse|Response
     */
    protected function notifyUser(User $user)
    {
        // if the user does not have this setting, continue
        if ($user->profile->setting_daily_update !== 1) {
            flash()->error('Error', 'User has daily updates disabled');

            return back();
        }

        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        $show_count = 12;
        $interests = [];
        $seriesList = [];
        $entityEvents = [];
        $tagEvents = [];
        $collectedIdList = [];

        // get the next x events they are attending
        $attendingEvents = $user->getAttendingToday()->take($show_count);
        foreach ($attendingEvents as $event) {
            /** @var \App\Models\Event $event */
            $collectedIdList[] = $event->id;
        }

        // build an array of events that are today based on what the user follows
        $entities = $user->getEntitiesFollowing();
        if (count($entities) > 0) {
            foreach ($entities as $entity) {
                $entityEvents = [];
                /** @var \App\Models\Entity $entity */
                if (count($entity->todaysEvents()) > 0) {
                    foreach ($entity->todaysEvents() as $todaysEvent) {
                        /** @var \App\Models\Event $todaysEvent */
                        if (!in_array($todaysEvent->id, $collectedIdList)) {
                            $entityEvents[] = $todaysEvent;
                            $collectedIdList[] = $todaysEvent->id;
                        }
                    }
                    if (count($entityEvents) > 0) {
                        $interests[$entity->name] = $entityEvents;
                    }
                }
            }
        }
        // build an array of future events based on tags the user follows
        $tags = $user->getTagsFollowing();
        if (count($tags) > 0) {
            foreach ($tags as $tag) {
                $tagEvents = [];
                /** @var \App\Models\Tag $tag */
                if (count($tag->todaysEvents()) > 0) {
                    foreach ($tag->todaysEvents() as $todaysEvent) {
                        if (!in_array($todaysEvent->id, $collectedIdList)) {
                            $tagEvents[] = $todaysEvent;
                            $collectedIdList[] = $todaysEvent->id;
                        }
                    }
                    if (count($tagEvents) > 0) {
                        $interests[$tag->name] = $tagEvents;
                    }
                }
            }
        }

        // build an array of series that the user is following
        $series = $user->getSeriesFollowing();
        if (count($series) > 0) {
            /** @var \App\Models\Series $s */
            foreach ($series as $s) {
                // if the series does not have NO SCHEDULE AND CANCELLED AT IS NULL
                if ($s->occurrenceType->name !== 'No Schedule' && (null === $s->cancelled_at)) {
                    // add matches to list
                    $next_date = $s->nextOccurrenceDate()->format('Y-m-d');

                    // today's date is the next series date
                    if ($next_date === Carbon::now()->format('Y-m-d')) {
                        $seriesList[] = $s;
                    }
                }
            }
        }

        Mail::to($user->email)->send(new UserUpdate($url, $site, $admin_email, $reply_email, $user, $attendingEvents, $seriesList, $interests));

        flash()->success('Success', 'A daily-style notification email was sent to  '.$user->name.' at '.$user->email);

        return back();
    }

    protected function notifyUserWeekly(User $user): RedirectResponse
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        $interests = [];
        $seriesList = [];
        $entityEvents = [];
        $tagEvents = [];
        $attendingIdList = [];
        $show_count = 36;

        // get the next x events they are attending
        $attendingEvents = $user->getAttendingFuture()->take($show_count);
        foreach ($attendingEvents as $event) {
            /** @var \App\Models\Event $event */
            $attendingIdList[] = $event->id;
        }

        // build an array of events that are upcoming based on what the user follows
        $entities = $user->getEntitiesFollowing();
        if (count($entities) > 0) {
            foreach ($entities as $entity) {
                $entityEvents = [];
                /** @var \App\Models\Entity $entity */
                if ($entity->futureEvents()->isNotEmpty()) {
                    foreach ($entity->futureEvents()->items() as $futureEvent) {
                        if (!in_array($futureEvent->id, $attendingIdList)) {
                            $entityEvents[] = $futureEvent;
                        }
                    }
                    if (count($entityEvents) > 0) {
                        $interests[$entity->name] = $entityEvents;
                    }
                }
            }
        }
        // build an array of future events based on tags the user follows
        $tags = $user->getTagsFollowing();
        if (count($tags) > 0) {
            foreach ($tags as $tag) {
                $tagEvents = [];
                /** @var \App\Models\Tag $tag */
                if ($tag->futureEvents()->isNotEmpty()) {
                    foreach ($tag->futureEvents() as $futureEvent) {
                        if (!in_array($futureEvent->id, $attendingIdList)) {
                            $tagEvents[] = $futureEvent;
                        }
                    }
                    if (count($tagEvents) > 0) {
                        $interests[$tag->name] = $tagEvents;
                    }
                }
            }
        }

        // build an array of series that the user is following
        $series = $user->getSeriesFollowing();
        if (count($series) > 0) {
            foreach ($series as $s) {
                // if the series does not have NO SCHEDULE AND CANCELLED AT IS NULL
                /** @var \App\Models\Series $s */
                if ($s->occurrenceType->name !== 'No Schedule' && (null === $s->cancelled_at)) {
                    // add matches to list
                    $seriesList[] = $s;
                }
            }
        }

        // if there are more than 0 events
        if ((null !== $attendingEvents && $attendingEvents->count() > 0) || (null !== $seriesList && count($seriesList) > 0) || (null !== $interests && count($interests) > 0)) {
            // send an email containing that list
            Mail::to($user->email)
                ->send(new WeeklyUpdate($url, $site, $admin_email, $reply_email, $user, $attendingEvents, $seriesList, $interests));
        }

        return back();
    }

    public function eventsAttending(
        User $user,
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        EventFilters $eventFilters
    ): JsonResponse {
        // initialize the list parameter session store for this route
        $listParamSessionStore->setBaseIndex('api_user_event');
        $listParamSessionStore->setKeyPrefix('api_user_event_index');

        // base query of events the user is attending
        $baseQuery = $user->getAttending()
            ->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id')
            ->leftJoin('entities as venue', 'events.venue_id', '=', 'venue.id')
            ->leftJoin('entities as promoter', 'events.promoter_id', '=', 'promoter.id')
            ->select('events.*');

        $listEntityResultBuilder
            ->setFilter($eventFilters)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['events.start_at' => 'asc']);

        // build the result set with filters, sorting and limit
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();
        $query = $listResultSet->getList();

        $events = $query->with([
                'visibility',
                'venue',
                'eventStatus',
                'eventType',
                'promoter',
                'series',
                'tags',
                'entities',
            ])
            ->paginate($listResultSet->getLimit());

        return response()->json(new EventCollection($events));
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['users.name' => 'Name', 'user_statuses.name' => 'Status', 'users.created_at' => 'Created At', 'last_active' => 'Last Active'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'userStatusOptions' => ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
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
