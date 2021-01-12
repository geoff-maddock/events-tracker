<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PagesController extends Controller
{
    protected $prefix;

    protected $defaultRpp;

    protected $defaultSortBy;

    protected $defaultSortOrder;

    protected $rpp;

    protected $page;

    protected $sort;

    protected $sortBy;

    protected $sortOrder;

    protected $defaultCriteria;

    protected $filters;

    protected $hasFilter;

    protected $dayOffset;

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'activity', 'tools']]);

        // default list variables
        $this->dayOffset = 0;

        // prefix for session storage
        $this->prefix = 'app.pages.';

        // default list variables
        $this->defaultRpp = 100;
        $this->defaultSortBy = 'created_at';
        $this->defaultSortOrder = 'desc';

        $this->rpp = 100;
        $this->page = 1;
        $this->sort = ['created_at', 'desc'];
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = null;
        parent::__construct();
    }

    /**
     * Checks if there is a valid filter.
     *
     * @param array $filters
     */
    public function hasFilter(array $filters): bool
    {
        $arr = $filters;
        unset($arr['rpp'], $arr['sortOrder'], $arr['sortBy'], $arr['page']);

        return count(array_filter($arr, function ($x) { return !empty($x); }));
    }

    /**
     * Update the page list parameters from the request.
     *
     * @param array $filters
     */
    protected function getPaging(array $filters): void
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        $this->rpp = $filters['rpp'] ?? $this->rpp;
    }

    /**
     * Update the page list parameters from the request.
     */
    protected function updatePaging($request)
    {
        // set starting day offset
        if ($request->input('day_offset')) {
            $this->dayOffset = $request->input('day_offset');
        }

        // set results per page
        if ($request->input('rpp')) {
            $this->rpp = $request->input('rpp');
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
     * @return View
     */
    public function search(Request $request)
    {
        $slug = $request->input('keyword');

        // override rpp, while not breaking template that tries to render
        $this->rpp = 20;

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
                    ->paginate($this->rpp);

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
                    ->paginate($this->rpp);

        $entities = Entity::where('name', 'like', '%' . $slug . '%')
                ->orWhereHas('tags', function ($q) use ($slug) {
                    $q->where('name', '=', ucfirst($slug));
                })
                ->orWherehas('aliases', function ($q) use ($slug) {
                    $q->where('name', '=', ucfirst($slug));
                })
                ->orderBy('entity_type_id', 'ASC')
                ->orderBy('name', 'ASC')
                ->paginate($this->rpp);

        $tags = Tag::where('name', 'like', '%' . $slug . '%')
                ->orderBy('name', 'ASC')
                ->simplePaginate($this->rpp);

        $users = User::where('name', 'like', '%' . $slug . '%')
                ->orderBy('name', 'ASC')
                ->simplePaginate($this->rpp);

        $threads = Thread::where('name', 'like', '%' . $slug . '%')
            ->orWhereHas('tags', function ($q) use ($slug) {
                $q->where('name', '=', ucfirst($slug));
            })
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

        return view('events.search', compact('events', 'entities', 'series', 'users', 'threads', 'tags', 'slug'));
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

    public function home(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // handle the request if ajax
        if ($request->ajax()) {
            return view('pages.4daysAjax')
                    ->with(['rpp' => $this->rpp, 'dayOffset' => $this->dayOffset])
                    ->render();
        }

        return view('pages.home')
                    ->with(['rpp' => $this->rpp, 'dayOffset' => $this->dayOffset]);
    }

    /**
     * @return View
     */
    public function activity(Request $request)
    {
        $this->middleware('auth');
        $offset = 0;

        if ($request->input('offset')) {
            $offset = $request->input('offset');
        }

//        // get all the filters from the session
//        $this->filters = $this->getFilters($request);

        // update filters based on the request input
        $this->setFilters($request, array_merge($this->getFilters($request), $request->input()));

        // get the merged filters
        $this->filters = $this->getFilters($request);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // flag that there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        // get the criteria given the request (could pass filters instead?)
        $query = $this->buildActivityCriteria($request);

        $activities = $query->take($this->rpp)
            ->offset($offset)
            ->get()
            ->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            });

        return view('pages.activity')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'filters' => $this->filters,
                'hasFilter' => $this->hasFilter,
            ])
            ->with(compact('activities'));
    }

    /**
     * Filter the list of entities.
     *
     * @return Response | View | string
     *
     * @throws \Throwable
     */
    public function filter(Request $request)
    {
        $offset = 0;
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
        $query = $this->buildActivityCriteria($request);

        $activities = $query->take($this->rpp)
            ->offset($offset)
            ->get()
            ->groupBy(static function ($activity) {
                return $activity->created_at->format('Y-m-d');
            });

        return view('pages.activity')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'filters' => $this->filters,
                'hasFilter' => $this->hasFilter,
            ])
            ->with(compact('activities'))
            ->render();
    }

    /**
     * Reset the filtering of activities.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function reset(Request $request)
    {
        $offset = 0;

        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        $this->hasFilter = 0;

        // get the criteria given the request (could pass filters instead?)
        $query = $this->buildActivityCriteria($request);

        $activities = $query->take($this->rpp)
            ->offset($offset)
            ->get()
            ->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            });

        return view('pages.activity')
            ->with(['rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'filters' => $this->filters,
                'hasFilter' => $this->hasFilter,
            ])
            ->with(compact('activities'));
    }

    /**
     * Reset the rpp, sort, order
     *
     *
     * @throws \Throwable
     */
    public function rppResetActivity(Request $request): RedirectResponse
    {
        // set the rpp, sort, direction to default values
        $this->setFilters($request, array_merge($this->getFilters($request), $this->getDefaultRppFilters()));

        return redirect()->route('pages.activity');
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

    protected function getDefaultRppFilters(): array
    {
        return [
            'rpp' => $this->defaultRpp,
            'sortBy' => $this->defaultSortBy,
            'sortOrder' => $this->defaultSortOrder
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

    /**
     * Builds the criteria from the session.
     */
    public function buildActivityCriteria(Request $request): Builder
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = Activity::orderBy($this->sortBy, $this->sortOrder);

        // add the criteria from the session
        // check request for passed filter values

        if (!empty($filters['name'])) {
            // getting name from the request
            $name = $filters['name'];
            $query->where('object_name', 'like', '%' . $name . '%');
        }

        if (!empty($filters['object_table'])) {
            // getting object table from the request
            $type = $filters['object_table'];
            $query->where('object_table', 'like', '%' . $type . '%');
        }

        if (!empty($filters['action'])) {
            $action = $filters['action'];

            // add has clause
            $query->whereHas('action', function ($q) use ($action) {
                $q->where('name', '=', $action);
            });
        }

        if (!empty($filters['user'])) {
            $user = $filters['user'];

            // add has clause
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('name', '=', $user);
            });
        }

        // change this - should be seperate
        if (!empty($filters['rpp'])) {
            $this->rpp = $filters['rpp'];
        }

        return $query;
    }

    public function tools(Request $request)
    {
        $this->middleware('auth');

        $user = $request->user();
        if (!$user->can('show_admin')) {
            die('cannot show admin)');
        }

        // get all the events with no photo
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

        // add to activity log - email address was invited
        // Activity::log($user, $this->user, 12);

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
