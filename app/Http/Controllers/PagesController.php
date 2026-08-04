<?php

namespace App\Http\Controllers;

use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
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
     * Update the page list parameters from the request.
     */
    protected function updatePaging(Request $request): void
    {
        // set starting day offset
        if ($request->input('day_offset')) {
            $this->offset = $request->input('day_offset');
        }

        // set results per page
        if ($request->input('limit')) {
            $this->limit = $request->input('limit');
        }
    }

    public function index(): View
    {
        $future_events = Event::where('start_at', '>=', Carbon::now())
                        ->orderBy('start_at', 'asc')
                        ->get();

        $past_events = Event::where('start_at', '<', Carbon::now())
                        ->orderBy('start_at', 'desc')
                        ->get();

        return view('events.index', compact('future_events', 'past_events'));
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

        // match the exact phrase, case-insensitive, in the name, short or description
        $like = '%'.addcslashes($keyword, '%_\\').'%';
        $textMatch = function ($query) use ($like) {
            $query->where('name', 'like', $like)
                ->orWhere('short', 'like', $like)
                ->orWhere('description', 'like', $like);
        };

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

    public function help(): View
    {
        return view('pages.help-tw');
    }

    public function about(): View
    {
        $menu = Menu::with('blogs.contentType')->find(1);

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
            ->with(['venue', 'tags', 'entities', 'eventType'])
            ->take(12)
            ->get();

        // Get recently added events
        $recentEvents = Event::where('start_at', '>=', Carbon::now())
            ->with(['venue', 'tags', 'entities', 'eventType'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // Check if user has followed content for showing recommendations section
        $hasFollowedContent = $user->getTagsFollowing()->isNotEmpty()
            || $user->getEntitiesFollowing()->isNotEmpty()
            || $user->getSeriesFollowing()->isNotEmpty();

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
        $popularEvents = Event::where('start_at', '>=', Carbon::now())
            ->withCount('attendees')
            ->with(['venue', 'tags', 'entities', 'eventType'])
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
            ->with(['entityType', 'roles', 'tags'])
            ->orderBy('followers_count', 'desc')
            ->take(12)
            ->get();

        // Get popular tags - tags with most events.
        // Eager-load each tag's thumbnail event plus its photos so the
        // view can show the event's primary photo without issuing a
        // fresh query per tag (N+1 flagged as EVENTREPO-WV). The limit(1) is
        // applied per tag via a window function; without it every visible
        // event for each top tag is hydrated (13k+ in prod), which exhausts
        // php-fpm's memory_limit.
        // Thumbnail event = soonest upcoming event with a primary photo,
        // falling back to the most recent past one (mirrors Tag::scopeWithGridThumbnail).
        $popularTags = Tag::withCount('events')
            ->with(['events' => function ($query) use ($user) {
                $query->visible($user)
                    ->whereHas('photos', function ($photo) {
                        $photo->where('photos.is_primary', 1);
                    })
                    ->orderByRaw('(start_at >= CURDATE()) desc')
                    ->orderByRaw('CASE WHEN start_at >= CURDATE() THEN start_at END asc')
                    ->orderBy('start_at', 'desc')
                    ->limit(1)
                    ->with('photos');
            }])
            ->orderBy('events_count', 'desc')
            ->take(24)
            ->get();

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
                        ]
                    );
    }

    /**
     * Get session filters.
     */
    protected function getFilters(Request $request): array
    {
        return $this->getAttribute($request, 'filters', $this->getDefaultFilters());
    }

    /**
     * Get user session attribute.
     */
    protected function getAttribute(Request $request, string $attribute, mixed $default = null): mixed
    {
        return $request->session()
            ->get($this->prefix.$attribute, $default);
    }

    /**
     * Get the default filters array.
     */
    protected function getDefaultFilters(): array
    {
        return [];
    }

    protected function getDefaultlimitFilters(): array
    {
        return [
            'limit' => $this->defaultLimit,
            'sort' => $this->defaultSort,
            'sortDirection' => $this->defaultSortDirection,
        ];
    }

    /**
     * Set filters attribute.
     */
    protected function setFilters(Request $request, array $input): void
    {
        $this->setAttribute('filters', $input, $request);
    }

    /**
     * Set user session attribute.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return void
     */
    protected function setAttribute($attribute, $value, Request $request)
    {
        $request->session()->put($this->prefix.$attribute, $value);
    }

    public function tools(Request $request): View|RedirectResponse
    {
        $this->middleware('auth');

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
        $email = $request->input('email');

        // check that a user with that email does not already exist.
        $users = User::where('email', 'like', '%'.$email.'%')->orderBy('name', 'ASC')->count();
        if ($users > 0) {
            flash()->success('Error', 'No email sent - a user with the address - '.$email.' - already exists on the site.');

            return back();
        }

        // email the user
        $this->inviteUser($email);

        Log::info('Email '.$email.' was invited to join the site');

        flash()->success('Success', 'An email was sent to '.$email.' inviting them to join the site');

        return back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function inviteUser(string $email)
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.admin');
        $site = config('app.app_name');
        $url = config('app.url');

        $show_count = 100;
        $events = [];
        $interests = [];

        $events = Event::future()->simplePaginate(10);

        // send an email inviting the user to join
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

        return back();
    }
}
