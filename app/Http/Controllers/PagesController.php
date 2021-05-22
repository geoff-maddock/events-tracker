<?php

namespace App\Http\Controllers;

use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Entity;
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

class PagesController extends Controller
{
    protected $prefix;

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

    protected $hasFilter;

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
    protected function updatePaging($request)
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

    /**
     * @return View
     */
    public function index()
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
     * Primary site searchbar action
     * @return View
     */
    public function search(Request $request)
    {
        $slug = $request->input('keyword');

        // override limit, while not breaking template that tries to render
        $this->limit = 20;

        // find matching events by entity, tag or series or name
        $events = Event::getByEntity(strtolower($slug))
                    ->orWhereHas('tags', function ($q) use ($slug) {
                        $q->where('name', '=', ucfirst($slug));
                    })
                    ->orWhereHas('series', function ($q) use ($slug) {
                        $q->where('name', '=', ucfirst($slug));
                    })
                    ->orWhere('name', 'like', '%' . $slug . '%')
                    ->where(function ($query) {
                        $query->visible($this->user);
                    })
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->paginate($this->limit);

        // find matching series by entity, tag or name
        $series = Series::getByEntity(strtolower($slug))
                    ->orWhereHas('tags', function ($q) use ($slug) {
                        $q->where('name', '=', ucfirst($slug));
                    })
                    ->orWhere('name', 'like', '%' . $slug . '%')
                    ->where(function ($query) {
                        $query->visible($this->user);
                    })
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->paginate($this->limit);

        // find entities by name, tags or aliases
        $entities = Entity::where('name', 'like', '%' . $slug . '%')
                ->orWhereHas('tags', function ($q) use ($slug) {
                    $q->where('name', '=', ucfirst($slug));
                })
                ->orWherehas('aliases', function ($q) use ($slug) {
                    $q->where('name', '=', ucfirst($slug));
                })
                ->orderBy('entity_type_id', 'ASC')
                ->orderBy('name', 'ASC')
                ->paginate($this->limit);

        // find tags by name
        $tags = Tag::where('name', 'like', '%' . $slug . '%')
                ->orderBy('name', 'ASC')
                ->simplePaginate($this->limit);

        // find users by name
        $users = User::where('name', 'like', '%' . $slug . '%')
                ->orderBy('name', 'ASC')
                ->simplePaginate($this->limit);

        // find threads by name
        $threads = Thread::where('name', 'like', '%' . $slug . '%')
            ->orWhereHas('tags', function ($q) use ($slug) {
                $q->where('name', '=', ucfirst($slug));
            })
            ->orderBy('name', 'ASC')
            ->paginate($this->limit);

        return view('pages.search', compact('events', 'entities', 'series', 'users', 'threads', 'tags', 'slug'));
    }

    public function help()
    {
        return view('pages.help');
    }

    public function about()
    {
        return view('pages.about');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function tos()
    {
        return view('pages.tos');
    }

    public function settings()
    {
        return view('pages.settings');
    }

    public function home(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        $listParamSessionStore->setBaseIndex('internal_page');
        $listParamSessionStore->setKeyPrefix('internal_page_home');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PagesController::class, 'home']));

        // updates sort, limit from request
        $this->updatePaging($request);

        // handle the request if ajax
        if ($request->ajax()) {
            return view('pages.4daysAjax')
                    ->with([
                        'limit' => $this->limit,
                        'dayOffset' => $this->offset,
                        'window' => $this->window
                    ])
                    ->render();
        }

        return view('pages.home')
                    ->with(
                        [
                            'limit' => $this->limit,
                            'dayOffset' => $this->offset,
                            'window' => $this->window
                        ]
                    );
    }

    /**
     * Get session filters.
     *
     * @return array
     */
    protected function getFilters(Request $request)
    {
        return $this->getAttribute('filters', $this->getDefaultFilters(), $request);
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getAttribute($attribute, $default = null, Request $request)
    {
        return $request->session()
            ->get($this->prefix . $attribute, $default);
    }

    /**
     * Get the default filters array.
     *
     * @return array
     */
    protected function getDefaultFilters()
    {
        return [];
    }

    protected function getDefaultlimitFilters(): array
    {
        return [
            'limit' => $this->defaultLimit,
            'sort' => $this->defaultSort,
            'sortDirection' => $this->defaultSortDirection
        ];
    }

    /**
     * Set filters attribute.
     */
    protected function setFilters(Request $request, array $input): void
    {
        // example: $input = array('filter_tag' => 'role', 'filter_name' => 'xano');
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
        $request->session()->put($this->prefix . $attribute, $value);
    }

    public function tools(Request $request)
    {
        $this->middleware('auth');

        $user = $request->user();
        if (!$user->can('show_admin')) {
            die('cannot show admin)');
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
        $users = User::where('email', 'like', '%' . $email . '%')->orderBy('name', 'ASC')->count();
        if ($users > 0) {
            flash()->success('Error', 'No email sent - a user with the address - ' . $email . ' - already exists on the site.' . count($users));

            return back();
        }

        // email the user
        $this->inviteUser($email);

        Log::info('Email ' . $email . ' was invited to join the site');

        flash()->success('Success', 'An email was sent to ' . $email . ' inviting them to join the site');

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
                    ->subject($site . ': Event Tracker Invite - ' . $dt->format('l F jS Y'));
            }
        );

        return back();
    }
}
