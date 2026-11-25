<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\UserFilters;
use App\Http\Requests\AdminPasswordResetRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UserRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\Signup\SignupRiskScorer;
use App\Services\DigestSender;
use App\Services\DigestSendResult;
use App\Services\EmailChangeVerifier;
use App\Services\ImageHandler;
use App\Services\SessionStore\ListParameterSessionStore;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Services\Calendar\ICalBuilder;
use App\Jobs\ExportUserDataJob;
use App\Services\DataExportService;
use App\Services\UserAccounts;
use Throwable;

class UsersController extends Controller
{
    use BuildsListPages;

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

    protected array $filters;

    protected array $tabs;

    protected array $defaultTabs;

    protected UserFilters $filter;

    public function __construct(UserFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'profile']]);
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
    ): string {
        return $this->userListPage($request, $listParamSessionStore, $listEntityResultBuilder, $this->defaultSortCriteria);
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
        return $this->userListPage($request, $listParamSessionStore, $listEntityResultBuilder, ['users.name' => 'asc']);
    }

    /**
     * The user list both list actions render.
     *
     * @param array<string, string> $defaultSort
     */
    private function userListPage(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        array $defaultSort,
    ): string {
        // needs select to make sure only user columns are returned
        $baseQuery = User::query()
            ->leftJoin('user_statuses', 'users.user_status_id', '=', 'user_statuses.id')
            ->select('users.*')
            ->addSelect(['last_active' => Activity::select('created_at')
                ->whereColumn('user_id', 'users.id')
                ->latest()
                ->take(1),
            ])
            ->withCasts(['last_active' => 'datetime']);

        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_user',
            'internal_user_index',
            action([UsersController::class, 'index']),
            $this->filter,
            $baseQuery,
            $defaultSort,
            allowedSortFields: array_keys($this->getListControlOptions()['sortOptions']),
        );

        $users = $listResultSet->getList()->with($this->cardEagerLoads($request->user()))->paginate($listResultSet->getLimit());

        return view('users.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
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
        $this->clearList($listParamSessionStore, 'internal_user', $request->get('key') ?? 'internal_user_index', filters: false);

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
        $this->clearList($listParamSessionStore, 'internal_user', $request->get('key') ?? 'internal_user_index', filters: true);

        return redirect()->route('users.index');
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
        return view('users.create-tw')->with($this->getFormOptions());
    }

    public function setTabs(Request $request): void
    {
        // Only persist a well-formed tabs array; a scalar like ?tabs=foo would otherwise
        // poison the session and break array access in the view.
        $tabs = $request->input('tabs');
        if (is_array($tabs)) {
            $request->session()->put($this->prefix.'tabs', $tabs);
        }
    }

    public function getTabs(Request $request): array
    {
        // Merge over the defaults so every expected key (events, following) is always
        // present, even when only a subset was supplied, e.g. ?tabs[events]=created
        // (EVENTREPO-TJ).
        $stored = $request->session()->get($this->prefix.'tabs', []);

        return array_merge($this->getDefaultTabs(), is_array($stored) ? $stored : []);
    }

    public function show(User $user, Request $request): RedirectResponse | View
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

            return redirect('users/'.$user->id);
        }

        // Determine if the current viewer can see the full profile
        // Full profile is visible if:
        // 1. The viewer is the profile owner
        // 2. The viewer is a superuser/admin
        // 3. The profile owner has enabled public_profile setting
        $canViewFullProfile = false;
        
        if ($this->user) {
            // User is signed in
            if ($this->user->id == $user->id || $this->user->id == config('app.superuser')) {
                // Viewing own profile or is superuser
                $canViewFullProfile = true;
            } elseif ($user->profile->setting_public_profile == 1) {
                // Profile owner has enabled public profile
                $canViewFullProfile = true;
            }
        } else {
            // Not signed in, check if profile is public
            if ($user->profile->setting_public_profile == 1) {
                $canViewFullProfile = true;
            }
        }

        // only the active events tab is rendered; load just that list, capped, with what the cards read
        $eagerLoad = EventsController::cardEventEagerLoad($this->user);
        // someone else's profile shows only the events the viewer may see
        $profileEvents = ($tabs['events'] ?? 'created') === 'attending'
            ? $user->getAttending()->visible($this->user)->with($eagerLoad)->orderBy('events.start_at', 'desc')->limit(20)->get()
            : $user->events()->visible($this->user)->with($eagerLoad)->limit(10)->get();

        // a short list of the entities this user created, shown like the entities
        // they follow (#2156); others see only the active ones
        $createdEntitiesQuery = Entity::query()->where('entities.created_by', '=', $user->id);
        if (! $this->user || ($this->user->id !== $user->id && ! $this->user->hasGroup('admin'))) {
            $createdEntitiesQuery->active();
        }
        $createdEntitiesCount = $canViewFullProfile ? (clone $createdEntitiesQuery)->count() : 0;
        $createdEntities = $createdEntitiesCount > 0
            ? $createdEntitiesQuery->with(Entity::CARD_EAGER_LOAD)->orderBy('entities.name')->limit(20)->get()
            : collect();

        // an account's bio and social handles stay private until it is trusted (#2312)
        $showProfileContent = $user->showsProfileContentTo($this->user);

        return view('users.show-tw', compact('user', 'tabs', 'canViewFullProfile', 'showProfileContent', 'profileEvents', 'createdEntities', 'createdEntitiesCount'));
    }

    public function profile(Request $request): RedirectResponse
    {
        // The 'auth' middleware (registered in the constructor) guarantees a signed-in
        // user here; guard anyway so a null user can never dereference ->id (EVENTREPO-HH).
        if (!$this->user) {
            return redirect()->guest(route('login'));
        }

        return redirect('users/'.$this->user->id);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        // only the account fields; like api/users, a new account starts pending, and
        // user_status_id (fillable) is never taken from the request (#2180)
        $user = User::create($request->validated());
        $user->user_status_id = UserStatus::PENDING;
        $user->save();

        // if there is no profile, create one
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();

        // add to activity log
        Activity::log($user, $this->user, 1);

        flash('Success', 'Your user has been created!');

        return redirect()->route('users.show', ['user' => $user]);
    }

    /**
     * Only the user themselves or an admin may edit/update/delete a user.
     */
    protected function authorizeUserChange(User $user): void
    {
        abort_unless(
            $this->user && ($this->user->id === $user->id || $this->user->can('grant_access')),
            403
        );
    }

    public function edit(User $user): View
    {
        $this->authorizeUserChange($user);

        return view('users.edit-tw', compact('user'))
            ->with($this->getFormOptions());
    }

    public function update(User $user, ProfileRequest $request): RedirectResponse
    {
        $this->authorizeUserChange($user);

        $input = $request->validated();

        // status and group membership are admin-only fields; strip them for
        // everyone else so a self-edit POST cannot escalate privileges
        if (!$this->user->can('grant_access')) {
            unset($input['user_status_id'], $input['group_list']);
        }

        $input['setting_weekly_update'] = isset($input['setting_weekly_update']) ? 1 : 0;
        $input['setting_daily_update'] = isset($input['setting_daily_update']) ? 1 : 0;
        $input['setting_instant_update'] = isset($input['setting_instant_update']) ? 1 : 0;
        $input['setting_forum_update'] = isset($input['setting_forum_update']) ? 1 : 0;
        $input['setting_notify_threads_by_follow'] = isset($input['setting_notify_threads_by_follow']) ? 1 : 0;
        $input['setting_public_profile'] = isset($input['setting_public_profile']) ? 1 : 0;
        $input['setting_feedback_requests'] = isset($input['setting_feedback_requests']) ? 1 : 0;

        $user->fill($input);
        // a self-made email change must be verified again (#2321)
        $emailChanges = app(EmailChangeVerifier::class);
        $emailChange = $emailChanges->prepare($user, $this->user, $request->ip());
        $user->save();
        // Some legacy users have no profile row yet (EVENTREPO-VW); create one on demand.
        $user->profile()->firstOrCreate([])->fill($input)->save();

        if (isset($input['group_list'])) {
            $user->groups()->sync($input['group_list']);
        }

        // a new account editing its own profile is rescored (#2312)
        if ($this->user->id === $user->id) {
            app(SignupRiskScorer::class)->assessProfileChange($user->load('profile'));
        }

        // add to activity log
        Activity::log($user, $this->user, 2);

        flash('Success', 'The user has been updated');

        if (null !== $emailChange) {
            $emailChanges->notify($user, $emailChange);

            // an admin correcting someone else's address doesn't need the prompt
            if ($this->user->id === $user->id) {
                $request->session()->flash('show_verification_notice', $user->email);
                $request->session()->flash('verification_notice_reason', 'email-changed');
            }
        }

        return redirect()->route('users.show', ['user' => $user]);
    }

    /**
     * @throws Exception
     */
    public function destroy(User $user, UserAccounts $accounts): RedirectResponse
    {
        $this->authorizeUserChange($user);

        $accounts->delete($user, $this->user);

        return redirect('users');
    }

    /**
     * Add a photo to a user.
     *
     * @throws ValidationException
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): void
    {
        $this->authorizeUserChange(User::findOrFail($id));

        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp|max:5120', // KB; matches Dropzone maxFilesize
        ]);

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

    /**
     * The user an admin action is aimed at, or null (with the error flashed).
     */
    private function actionTarget(int $id): ?User
    {
        // the routes are behind 'auth'; guard anyway so a null user is never dereferenced
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return null;
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');
        }

        return $user;
    }

    /**
     * Mark user as activated.
     */
    public function activate(int $id, UserAccounts $accounts): RedirectResponse
    {
        if (!$user = $this->actionTarget($id)) {
            return back();
        }

        $emailed = $accounts->activate($user, $this->user);

        flash()->success('Success', 'User '.$user->name.' is now activated.');
        if (!$emailed) {
            flash()->error('Email not sent', 'User '.$user->name.' was activated, but the notification email could not be sent.');
        }

        return back();
    }

    /**
     * Mark user as suspended.
     */
    public function suspend(int $id, UserAccounts $accounts): RedirectResponse
    {
        if (!$user = $this->actionTarget($id)) {
            return back();
        }

        $emailed = $accounts->suspend($user, $this->user);

        flash()->success('Success', 'User '.$user->name.' is now suspended.');
        if (!$emailed) {
            flash()->error('Email not sent', 'User '.$user->name.' was suspended, but the notification email could not be sent.');
        }

        return back();
    }

    /**
     * Send a site update reminder to the user.
     */
    public function reminder(int $id, DigestSender $sender): RedirectResponse
    {
        if (!$user = $this->actionTarget($id)) {
            return back();
        }

        // sending the email is the whole point of this action, so report the outcome honestly
        match ($sender->daily($user, $this->user)) {
            DigestSendResult::Disabled => flash()->error('Error', 'User has daily updates disabled'),
            DigestSendResult::Failed => flash()->error('Email not sent', 'The daily-style notification email to '.$user->email.' could not be sent.'),
            default => flash()->success('Success', 'A reminder email was sent to  '.$user->name.' at '.$user->email),
        };

        return back();
    }

    /**
     * Send a weekly site update reminder to the user.
     */
    public function weekly(int $id, DigestSender $sender): RedirectResponse
    {
        if (!$user = $this->actionTarget($id)) {
            return back();
        }

        // the weekly update may be sent by the account owner or by an admin
        if ($this->user->id !== $user->id && !$this->user->can('grant_access')) {
            abort(403);
        }

        match ($sender->weekly($user, $this->user)) {
            DigestSendResult::Disabled => flash()->error('Error', 'User has weekly updates disabled'),
            DigestSendResult::NothingToSend => flash()->message('Nothing to send', 'There is nothing in the week ahead for '.$user->name.', so no weekly update was sent.'),
            DigestSendResult::Failed => flash()->error('Email not sent', 'The weekly notification email to '.$user->email.' could not be sent.'),
            DigestSendResult::Sent => flash()->success('Success', 'A weekly reminder email was sent to  '.$user->name.' at '.$user->email),
        };

        return back();
    }

    /**
     * Delete the user.
     */
    public function delete(int $id, UserAccounts $accounts): RedirectResponse
    {
        if (!$user = $this->actionTarget($id)) {
            return back();
        }

        $accounts->delete($user, $this->user);

        flash()->success('Success', 'User '.$user->name.' is now deleted.');

        return back();
    }

    /**
     * Return the users events in iCal format.
     *
     * @return Response|RedirectResponse|string
     */
    public function ical(
        int $id,
        Request $request,
        ICalBuilder $iCalBuilder)
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

        // get the next x events they are attending
        $events = $user->getAttendingFuture()
            ->filter(fn (Event $event) => $event->isVisibleTo($this->user))
            ->take(self::DEFAULT_SHOW_COUNT);
        // a short run's dates each become a VEVENT (#2375)
        $events->loadMissing('showings');

        // create a calendar object
        $calendar = $iCalBuilder->buildCalendar($this->user->getFullNameAttribute().' Calendar', $events);

        return $calendar;
    }

    public function purge(Request $request, UserAccounts $accounts): RedirectResponse
    {
        abort_unless($request->user()?->can('grant_access'), 403);

        $names = $accounts->purgeUnverified();

        if ($names === []) {
            flash()->error('Error', 'There are no users to purge.');
        } else {
            flash()->success('Success', 'These users have been purged: '.implode(', ', $names));
        }

        return back();
    }

    /**
     * Show form for admin to reset a user's password.
     */
    public function showResetPassword(int $id): View|RedirectResponse
    {
        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        return view('users.reset-password', compact('user'));
    }

    /**
     * Admin reset of user password.
     */
    public function resetPassword(int $id, AdminPasswordResetRequest $request): RedirectResponse
    {
        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        // update the user's password
        $user->password = Hash::make($request->input('password'));
        $user->save();

        // add to activity log
        Activity::log($user, $this->user, Action::PASSWORD_RESET);

        Log::info('User '.$user->name.' password was reset by admin '.$this->user->name);

        flash()->success('Success', 'Password for user '.$user->name.' has been reset.');

        return redirect()->route('users.show', ['user' => $user]);
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

    /**
     * Eager loads needed to render users/card-tw without N+1 queries.
     */
    protected function cardEagerLoads(?User $viewer): array
    {
        $loads = [
            'status',
            'profile',
            'photos',
            'attendingEvents' => function ($q) use ($viewer) {
                $q->future()->visible($viewer)->with('photos');
            },
        ];

        // group badges are only shown to admins (#2233)
        if ($viewer?->can('admin')) {
            $loads[] = 'groups';
        }

        return $loads;
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilityOptions' => ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'userStatusOptions' => ['' => ''] + UserStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'groupOptions' => Group::orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Export user data as a ZIP file with JSON and images.
     */
    public function exportData(int $id, Request $request): RedirectResponse
    {
        // Check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');
            return back();
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');
            return back();
        }

        // Authorization: user can only export their own data, or superuser can export any
        if ($this->user->id != $user->id && $this->user->id != config('app.superuser')) {
            flash()->error('Error', 'You are not authorized to export this user\'s data.');
            return back();
        }

        // Dispatch the export job
        ExportUserDataJob::dispatch($user);

        // Add to activity log
        Activity::log($user, $this->user, Action::EXPORT);

        Log::info('User data export requested for user: ' . $user->name . ' by: ' . $this->user->name);

        flash()->success('Success', 'Your data export has been queued. You will receive an email with a download link when it is ready.');

        return redirect()->route('users.show', ['user' => $user]);
    }

    /**
     * Re-enable the post-signup "Getting To Know You" onboarding prompt (issue #901)
     * by clearing the completed/dismissed flags on the user's profile.
     */
    public function restartOnboarding(int $id, Request $request): RedirectResponse
    {
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$user = User::find($id)) {
            flash()->error('Error', 'No such user');

            return back();
        }

        // Authorization: a user can restart their own onboarding; the superuser can restart anyone's.
        if ($this->user->id != $user->id && $this->user->id != config('app.superuser')) {
            flash()->error('Error', 'You are not authorized to restart onboarding for this user.');

            return back();
        }

        $profile = $user->profile;
        if (!$profile) {
            $profile = new Profile(['user_id' => $user->id]);
            $profile->user_id = $user->id;
        }

        $profile->onboarding_completed_at = null;
        $profile->onboarding_dismissed_at = null;
        $profile->save();

        flash()->success('Success', 'Onboarding has been re-enabled.');

        // When restarting their own onboarding, send the user home and force the
        // prompt to reappear on the next page even if they already follow things.
        if ($this->user->id == $user->id) {
            return redirect('/')->with('show_onboarding', true);
        }

        return redirect()->route('users.show', ['user' => $user]);
    }

    /**
     * Download a generated export file via signed URL.
     */
    public function downloadExport(string $filename, Request $request, DataExportService $exportService): BinaryFileResponse
    {
        $path = $exportService->findExportPath($filename);

        if (!$path || !is_file($path)) {
            abort(404);
        }

        $response = response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
        ]);

        // one person's data: no proxy or browser cache keeps a copy past the link's
        // life. Set on the response: a file download marks itself public when made.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
