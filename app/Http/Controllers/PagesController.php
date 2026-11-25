<?php

namespace App\Http\Controllers;

use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Services\BestEffortMailer;
use App\Services\EventDateRange;
use App\Services\SearchService;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PagesController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected int $offset;

    protected int $defaultOffset;

    protected int $window;

    protected int $defaultWindow;

    protected array $filters;

    protected bool $hasFilter;

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'activity', 'tools']]);

        // prefix for session storage
        $this->prefix = 'app.pages.';

        // default list variables
        $this->defaultLimit = 100;
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultOffset = 0;
        $this->defaultWindow = 4;

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->offset = $this->defaultOffset;
        $this->window = $this->defaultWindow;

        $this->defaultSortCriteria = ['created_at' => 'desc'];

        $this->hasFilter = false;

        parent::__construct();
    }

    /**
     * Primary site searchbar action.
     */
    public function search(Request $request, SearchService $searchService): View
    {
        $search = (string) $request->input('keyword', '');
        $this->limit = 20;

        $results = $searchService->all($search, $this->user, $this->limit);

        return view('pages.search', array_merge($results, ['search' => $search]));
    }

    
    /**
     * Automatically relate entities to returned events
     */
    public function autoRelateEntity(int $id, Request $request): RedirectResponse
    {
        if (!$this->user || !$this->user->can('show_admin')) {
            flash()->error('Error', 'You do not have permission to auto-relate entities');

            return back();
        }

        // load the entity
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        $keyword = trim((string) $request->input('keyword'));

        if ($keyword === '') {
            flash()->error('Error', 'No keyword was provided to match against');

            return back();
        }

        $textMatch = $this->keywordMatch($keyword);

        $events = Event::where($textMatch)
            ->visible($this->user)
            ->get();

        foreach ($events as $event) {
            $event->entities()->syncWithoutDetaching([$entity->id]);
        }

        // find matching series the same way
        $seriesList = Series::where($textMatch)
            ->visible($this->user)
            ->get();

        foreach ($seriesList as $series) {
            $series->entities()->syncWithoutDetaching([$entity->id]);
        }

        flash()->success('Success', sprintf('Related "%s" to %d matching event(s) and %d matching series.', $entity->name, count($events), count($seriesList)));

        return redirect()->route('pages.search', compact('keyword'));
    }

    /**
     * Automatically set a series on returned events that aren't already in a series
     */
    public function autoRelateSeries(int $id, Request $request): RedirectResponse
    {
        if (!$this->user || !$this->user->can('show_admin')) {
            flash()->error('Error', 'You do not have permission to auto-relate series');

            return back();
        }

        // load the series
        if (!$series = Series::find($id)) {
            flash()->error('Error', 'No such series');

            return back();
        }

        $keyword = trim((string) $request->input('keyword'));

        if ($keyword === '') {
            flash()->error('Error', 'No keyword was provided to match against');

            return back();
        }

        $textMatch = $this->keywordMatch($keyword);

        // only claim events that don't already belong to a series
        $events = Event::where($textMatch)
            ->whereNull('series_id')
            ->visible($this->user)
            ->get();

        foreach ($events as $event) {
            $event->series_id = $series->id;
            $event->save();
        }

        $skipped = Event::where($textMatch)
            ->whereNotNull('series_id')
            ->where('series_id', '!=', $series->id)
            ->visible($this->user)
            ->count();

        flash()->success('Success', sprintf('Added %d matching event(s) to series "%s"; skipped %d already in another series.', count($events), $series->name, $skipped));

        return redirect()->route('pages.search', compact('keyword'));
    }

    /**
     * Query constraint matching the exact phrase, case-insensitive, in the name, short or description
     */
    private function keywordMatch(string $keyword): \Closure
    {
        $like = '%'.addcslashes($keyword, '%_\\').'%';

        return function ($query) use ($like) {
            $query->where('name', 'like', $like)
                ->orWhere('short', 'like', $like)
                ->orWhere('description', 'like', $like);
        };
    }

    public function help(): View
    {
        return view('pages.help-tw');
    }

    public function apiGuide(): View
    {
        return view('pages.api-guide-tw');
    }

    public function about(): View
    {
        $menu = Menu::with([
            'blogs' => fn ($query) => $query->visible($this->user),
            'blogs.contentType',
        ])->find(1);

        return view('pages.about-tw', compact('menu'));
    }

    public function privacy(): View
    {
        return view('pages.privacy-tw');
    }

    public function tos(): View
    {
        return view('pages.tos-tw');
    }

    public function radar(Request $request): View
    {
        $user = $request->user();

        // Get events the user is attending (future only)
        $attendingEvents = $user->getAttendingFuture();

        // Get recommended events based on followed entities, tags, and series
        $recommendedEvents = $user->getRecommendedEvents()
            ->visible($user)
            ->with(EventsController::cardEventEagerLoad($user))
            ->take(12)
            ->get();

        // Get recently added events
        $recentEvents = Event::query()->occurringBetween(Carbon::now())
            ->visible($user)
            ->with(EventsController::cardEventEagerLoad($user))
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // Check if user has followed content for showing recommendations section
        $hasFollowedContent = $user->recommendationFollows() !== null;

        return view('pages.radar-tw', compact(
            'attendingEvents',
            'recommendedEvents',
            'recentEvents',
            'hasFollowedContent'
        ));
    }

    public function allModules(): View
    {
        return view('pages.all-modules-tw');
    }

    public function popular(Request $request): View
    {
        $user = $request->user();

        // Get popular events - upcoming events with most attendees
        $popularEvents = Event::query()->occurringBetween(Carbon::now())
            ->visible($user)
            ->withCount('attendees')
            ->with(EventsController::cardEventEagerLoad($user))
            ->orderBy('attendees_count', 'desc')
            ->take(12)
            ->get();

        // Get popular entities - entities with most followers (using subquery since followers() returns Collection)
        $popularEntities = Entity::whereHas('entityStatus', function ($query) {
                $query->where('name', 'Active');
            })
            ->select('entities.*')
            ->selectSub(
                'SELECT COUNT(*) FROM follows WHERE follows.object_type = "entity" AND follows.object_id = entities.id',
                'followers_count'
            )
            ->with(Entity::CARD_EAGER_LOAD)
            ->orderBy('followers_count', 'desc')
            ->take(12)
            ->get();

        // the most used tags with a thumbnail each: the same for everyone, and kept for half an hour
        $popularTags = Tag::mostUsed(24);

        return view('pages.popular-tw', compact(
            'popularEvents',
            'popularEntities',
            'popularTags',
            'user'
        ));
    }

    public function home(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $date = ''
    ): View | string {
        $listParamSessionStore->setBaseIndex('internal_page');
        $listParamSessionStore->setKeyPrefix('internal_page_home');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PagesController::class, 'home']));

        // default to today if no date provided
        if (empty($date)) {
            $date = Carbon::now('America/New_York')->format('Y-m-d');
        }

        // use the window to get the last date and set the criteria between
        $next_day = Carbon::parse($date)->addDays(1);
        $next_day_window = Carbon::parse($date)->addDays($this->defaultWindow);
        $prev_day = Carbon::parse($date)->subDays(1);
        $prev_day_window = Carbon::parse($date)->subDays($this->defaultWindow);

        $dateRange = new EventDateRange();
        // the window's cards and scheduled series, a list per day
        $eventsByDay = Event::cardsByDay($date, $this->defaultWindow, $this->user);
        $seriesByDay = Series::byDay($date, $this->defaultWindow, $eventsByDay);

        // handle the request if ajax
        if ($request->ajax()) {
            return view('events.4daysAjax-tw')
                    ->with([
                        'date' => $date,
                        'window' => $this->defaultWindow,
                        'next_day' => $next_day,
                        'next_day_window' => $next_day_window,
                        'prev_day' => $prev_day,
                        'prev_day_window' => $prev_day_window,
                        'hasPrev' => $dateRange->contains($prev_day),
                        'hasPrevWindow' => $dateRange->contains($prev_day_window),
                        'hasNext' => $dateRange->contains($next_day),
                        'hasNextWindow' => $dateRange->contains($next_day_window),
                        'eventsByDay' => $eventsByDay,
                        'seriesByDay' => $seriesByDay,
                    ])
                    ->render();
        }

        return view('pages.home-tw')
                    ->with(
                        [
                            'date' => $date,
                            'window' => $this->defaultWindow,
                            'next_day' => $next_day,
                            'next_day_window' => $next_day_window,
                            'prev_day' => $prev_day,
                            'prev_day_window' => $prev_day_window,
                            'hasPrev' => $dateRange->contains($prev_day),
                            'hasPrevWindow' => $dateRange->contains($prev_day_window),
                            'hasNext' => $dateRange->contains($next_day),
                            'hasNextWindow' => $dateRange->contains($next_day_window),
                            'eventsByDay' => $eventsByDay,
                            'seriesByDay' => $seriesByDay,
                        ]
                    );
    }

    public function tools(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user->can('show_admin')) {
            flash()->error('Unauthorized', 'You cannot view the admin tools.');

            return redirect()->route('home');
        }

        // get all the events with a link but no photo
        $events = Event::has('photos', '<', 1)
            ->where('primary_link', '<>', '')
            ->where('primary_link', 'like', '%facebook%')
            ->get();

        return view('pages.tools-tw', compact('events'));
    }

    /**
     * @return View|RedirectResponse
     */
    public function invite(Request $request)
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];

        // check that a user with that email does not already exist (exact match, not LIKE)
        $users = User::where('email', $email)->count();
        if ($users > 0) {
            flash()->error('Error', 'No email sent - a user with the address - '.$email.' - already exists on the site.');

            return back();
        }

        // email the user
        if (!$this->inviteUser($email)) {
            flash()->error('Email not sent', 'The invite to '.$email.' could not be sent. Please try again later.');

            return back();
        }

        Log::info('Email '.$email.' was invited to join the site');

        flash()->success('Success', 'An email was sent to '.$email.' inviting them to join the site');

        return back();
    }

    /**
     * Send the invite. Returns false when the mail transport rejected it, so
     * the caller can report that honestly rather than claiming it was sent.
     */
    protected function inviteUser(string $email): bool
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        $show_count = 100;
        $events = [];
        $interests = [];

        // mailed to an outside address, so public events only
        $events = Event::future()->visible(null)->simplePaginate(10);

        // send an email inviting the user to join
        return (new BestEffortMailer())->attempt(function () use ($email, $events, $url, $site, $admin_email, $reply_email) {
            Mail::send(
                'emails.invite',
                ['email' => $email,  'events' => $events, 'url' => $url, 'site' => $site],
                function ($m) use ($email, $admin_email, $reply_email, $site) {
                    $m->from($reply_email, $site);

                    $dt = Carbon::now();
                    $m->to($email, $email)
                        ->bcc($admin_email)
                        ->subject($site.': Event Tracker Invite - '.$dt->format('l F jS Y'));
                }
            );
        }, ['invite_email' => $email]);
    }
}
