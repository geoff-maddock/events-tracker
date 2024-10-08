<?php

namespace App\Http\Controllers;

use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Str;

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
    public function search(Request $request): View
    {
        $search = $request->input('keyword');
        $searchSlug = Str::slug($search, '-');

        // override limit, while not breaking template that tries to render
        $this->limit = 20;

        // find matching events by entity, tag or series or name
        $eventQuery = Event::getByEntity(strtolower($searchSlug))
                    ->with('visibility', 'venue','tags', 'entities','series','eventType','threads')
                    ->orWhereHas('tags', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhereHas('series', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhereHas('venue', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhereHas('promoter', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->where(function ($query) {
                        $query->visible($this->user);
                    })
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC');
        
        $eventsCount = $eventQuery->count();
        $events = $eventQuery->paginate($this->limit);

        // find matching series by entity, tag or name
        $seriesQuery = Series::getByEntity(strtolower($searchSlug))
                    ->with('visibility', 'venue','tags', 'entities','eventType','threads','occurrenceType','occurrenceWeek','occurrenceDay')
                    ->orWhereHas('tags', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->where(function ($query) {
                        $query->visible($this->user);
                    })
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC');

        $seriesCount = $seriesQuery->count();
        $series = $seriesQuery->with('occurrenceWeek','occurrenceType','occurrenceDay')->paginate($this->limit);

        // find entities by name, tags or aliases
        $entitiesQuery = Entity::where('name', 'like', '%'.$search.'%')
                ->with('tags', 'events','entityType','locations','entityStatus','user')
                ->where('entity_status_id','<>',EntityStatus::UNLISTED)
                ->orWhereHas('tags', function ($q) use ($search) {
                    $q->where('name', '=', ucfirst($search));
                })
                ->orWherehas('aliases', function ($q) use ($search) {
                    $q->where('name', '=', ucfirst($search));
                })
                ->orderBy('entity_type_id', 'ASC')
                ->orderBy('name', 'ASC');

        $entitiesCount = $entitiesQuery->count();
        $entities = $entitiesQuery->paginate($this->limit);

        // find tags by name
        $tagsQuery = Tag::where('name', 'like', '%'.$search.'%')
                ->orderBy('name', 'ASC');

        $tagsCount = $tagsQuery->count();
        $tags = $tagsQuery->simplePaginate($this->limit);

        // find users by name
        $usersQuery = User::where('name', 'like', '%'.$search.'%')
                ->orderBy('name', 'ASC');

        $usersCount = $usersQuery->count();
        $users = $usersQuery->simplePaginate($this->limit);

        // find threads by name
        $threadsQuery = Thread::with('visibility','entities','tags','posts','event','user')->where('name', 'like', '%'.$search.'%')
            ->orWhereHas('tags', function ($q) use ($search) {
                $q->where('name', '=', ucfirst($search));
            })
            ->orderBy('name', 'ASC');
            
        $threadsCount = $threadsQuery->count();
        $threads = $threadsQuery->paginate($this->limit);

        return view('pages.search', compact('events', 'eventsCount', 'entities', 'entitiesCount', 'series', 'seriesCount', 'users', 'usersCount', 'threads', 'threadsCount', 'tags', 'tagsCount', 'search'));
    }

    
    /**
     * Automatically relate entities to returned events
     */
    public function autoRelateEntity(int $id, Request $request): RedirectResponse
    {
        // load the entity
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }
        $keyword = $request->input('keyword');
        $search = $request->input('keyword');
        $searchSlug = Str::slug($search, '-');

        // override limit, while not breaking template that tries to render
        $this->limit = 20;

        // find matching events by entity, tag or series or name
        $eventQuery = Event::getByEntity(strtolower($searchSlug))
                    ->with('visibility', 'venue','tags', 'entities','series','eventType','threads')
                    ->orWhereHas('tags', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhereHas('series', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhereHas('venue', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhereHas('promoter', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->where(function ($query) {
                        $query->visible($this->user);
                    })
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC');
        
        $eventsCount = $eventQuery->count();
        $events = $eventQuery->get();

        foreach ($events as $event) {
            $event->entities()->syncWithoutDetaching([$entity->id]);
        }


        // find matching series by entity, tag or name
        $seriesQuery = Series::getByEntity(strtolower($searchSlug))
                    ->with('visibility', 'venue','tags', 'entities','eventType','threads','occurrenceType','occurrenceWeek','occurrenceDay')
                    ->orWhereHas('tags', function ($q) use ($search) {
                        $q->where('name', '=', ucfirst($search));
                    })
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->where(function ($query) {
                        $query->visible($this->user);
                    })
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC');

        $seriesCount = $seriesQuery->count();
        $series = $seriesQuery->get();
        foreach ($series as $s) {
            $s->entities()->syncWithoutDetaching([$entity->id]);
        }
        $series = $seriesQuery->with('occurrenceWeek','occurrenceType','occurrenceDay')->paginate($this->limit);

          // find entities by name, tags or aliases
          $entitiesQuery = Entity::where('name', 'like', '%'.$search.'%')
          ->with('tags', 'events','entityType','locations','entityStatus','user')
          ->where('entity_status_id','<>',EntityStatus::UNLISTED)
          ->orWhereHas('tags', function ($q) use ($search) {
              $q->where('name', '=', ucfirst($search));
          })
          ->orWherehas('aliases', function ($q) use ($search) {
              $q->where('name', '=', ucfirst($search));
          })
          ->orderBy('entity_type_id', 'ASC')
          ->orderBy('name', 'ASC');

        $entitiesCount = $entitiesQuery->count();
        $entities = $entitiesQuery->paginate($this->limit);

        // find tags by name
        $tagsQuery = Tag::where('name', 'like', '%'.$search.'%')
                ->orderBy('name', 'ASC');

        $tagsCount = $tagsQuery->count();
        $tags = $tagsQuery->simplePaginate($this->limit);

        // find users by name
        $usersQuery = User::where('name', 'like', '%'.$search.'%')
                ->orderBy('name', 'ASC');

        $usersCount = $usersQuery->count();
        $users = $usersQuery->simplePaginate($this->limit);

        // find threads by name
        $threadsQuery = Thread::with('visibility','entities','tags','posts','event','user')->where('name', 'like', '%'.$search.'%')
            ->orWhereHas('tags', function ($q) use ($search) {
                $q->where('name', '=', ucfirst($search));
            })
            ->orderBy('name', 'ASC');
            
        $threadsCount = $threadsQuery->count();
        $threads = $threadsQuery->paginate($this->limit);

        return redirect()->route('pages.search', compact('keyword'));
    }

    public function help(): View
    {
        return view('pages.help');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function tos(): View
    {
        return view('pages.tos');
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

        // use the window to get the last date and set the criteria between
        $next_day = Carbon::parse($date)->addDays(1);
        $next_day_window = Carbon::parse($date)->addDays($this->defaultWindow);
        $prev_day = Carbon::parse($date)->subDays(1);
        $prev_day_window = Carbon::parse($date)->subDays($this->defaultWindow);

        // handle the request if ajax
        if ($request->ajax()) {
            return view('pages.4daysAjax')
                    ->with([
                        'date' => $date,
                        'window' => $this->defaultWindow,
                        'next_day' => $next_day,
                        'next_day_window' => $next_day_window,
                        'prev_day' => $prev_day,
                        'prev_day_window' => $prev_day_window,
                    ])
                    ->render();
        }

        return view('pages.home')
                    ->with(
                        [
                            'date' => $date,
                            'window' => $this->defaultWindow,
                            'next_day' => $next_day,
                            'next_day_window' => $next_day_window,
                            'prev_day' => $prev_day,
                            'prev_day_window' => $prev_day_window,
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

    public function tools(Request $request): View
    {
        $this->middleware('auth');

        $user = $request->user();
        if (!$user->can('show_admin')) {
            exit('cannot show admin)');
        }

        // get all the events with a link but no photo
        $events = Event::has('photos', '<', 1)
            ->where('primary_link', '<>', '')
            ->where('primary_link', 'like', '%facebook%')
            ->get();

        return view('pages.tools', compact('events'));
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
