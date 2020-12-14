<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Http\Requests\ThreadRequest;
use App\Models\Like;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ThreadsController extends Controller
{
    // define a list of variables
    protected string $prefix;

    protected int $rpp;

    protected int $page;

    protected int $defaultRpp;

    protected string $defaultSortBy;

    protected string $defaultSortOrder;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected array $defaultCriteria;

    protected bool $hasFilter;

    protected array $filters;

    protected array $criteria;

    protected ?Thread $thread;

    public function __construct(Thread $thread)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);
        $this->thread = $thread;

        // prefix for session storage
        $this->prefix = 'app.threads.';

        // default list variables - move to function that set from session or default
        $this->defaultRpp = 10;
        $this->defaultSortBy = 'created_at';
        $this->defaultSortOrder = 'desc';

        $this->rpp = 10;
        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = [];
        $this->hasFilter = 1;

        parent::__construct();
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
     * Set user session attribute.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function setAttribute(string $attribute, $value, Request $request): void
    {
        $request->session()
            ->put($this->prefix . $attribute, $value);
    }

    /**
     * Display a listing of the resource.
     *
     * @return View | string
     *
     * @throws \Throwable
     */
    public function index(Request $request)
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

        // initialize the query
        $query = $this->buildCriteria($request);

        // get the threads
        $threads = $query->with('visibility')->paginate($this->rpp);

        // filter only public threads or those created by the logged in user
        $threads->filter(function ($e) {
            return ('Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        // if the user is not authenticated, filter out any guarded threads
        if (!Auth::check()) {
            $threads->filter(function ($e) {
                return 'Guarded' !== $e->visibility->name;
            });
        }

        // return json only
        if (request()->wantsJson()) {
            return $threads;
        }

        return view('threads.index')
                    ->with(compact('threads'))
                    ->with(['rpp' => $this->rpp,
                        'sortBy' => $this->sortBy,
                        'sortOrder' => $this->sortOrder,
                        'hasFilter' => $this->hasFilter,
                        'filters' => $this->filters,
                    ])->render();
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
        if (isset($filters['rpp']) && is_numeric($filters['rpp'])) {
            $this->rpp = $filters['rpp'];
        } else {
            $this->rpp = $this->defaultRpp;
        }
    }

    /**
     * Update the page list parameters from the request.
     *
     */
    protected function updatePaging(Request $request): void
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        }

        // set sort direction
        if ($request->input('sort_direction')) {
            $this->sortOrder = $request->input('sort_direction');
        }

        if (!empty($request->input('rpp')) && is_numeric($request->input('rpp'))) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Builds the criteria from the session.
     *
     * @return Builder
     */
    public function buildCriteria(Request $request): Builder
    {
        // get all the filters from the session and put into an array
        $filters = $this->getFilters($request);

        // base criteria
        $query = Thread::orderBy($this->sortBy, $this->sortOrder);

        // add the criteria from the session
        // check request for passed filter values

        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%' . $name . '%');
        }

        if (!empty($filters['filter_user'])) {
            $user = $filters['filter_user'];

            // add has clause
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('name', '=', $user);
            });
        }

        if (!empty($filters['filter_tag'])) {
            $tag = $filters['filter_tag'];
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });
        }

        // change this - should be seperate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

    /**
     * Filter the list of events.
     *
     * @return View
     *
     * @internal param $Request
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

        // get threads
        $threads = $query->paginate($this->rpp);
        $threads->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        return view('threads.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter, 'filters' => $this->filters,
            ])
            ->with(compact('threads'));
    }

    /**
     * Set filters attribute.
     */
    public function setFilters(Request $request, array $input)
    {
        $this->setAttribute('filters', $input, $request);
    }

    /**
     * Reset the filtering of entities.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function reset(Request $request)
    {
        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        $this->hasFilter = 0;

        // default
        $query = Thread::where(function ($query) {
            $query->visible($this->user);
        })
                    ->orderBy($this->sortBy, $this->sortOrder)
                    ->orderBy('name', 'ASC');

        // paginate
        $threads = $query->paginate($this->rpp);

        $filters = [];

        return view('threads.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter,  'filters' => $filters])
            ->with(compact('threads'))
            ->render();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexAll(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $threads = Thread::orderBy('created_at', 'desc')->paginate(1000000);
        $threads->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('threads.index')
                    ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
                    ->with(compact('threads'));
    }

    /**
     * Display a listing of threads by category.
     *
     * @param $slug
     *
     * @return View
     */
    public function indexCategories(Request $request, $slug)
    {
        $hasFilter = 1;

        // updates sort, rpp from request
        $this->updatePaging($request);

        $threads = Thread::getByCategory(strtolower($slug))
                    ->where(function ($query) {
                        $query->visible($this->user);
                    })
                    ->orderBy($this->sortBy, 'ASC')
                    ->paginate($this->rpp);

        return view('threads.index')
                    ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'slug' => $slug, 'hasFilter' => $hasFilter])
                    ->with(compact('threads'));
    }

    /**
     * Display a listing of threads by tag.
     *
     * @param $tag
     *
     * @return View
     */
    public function indexTags(Request $request, $tag)
    {
        $hasFilter = 1;

        // updates sort, rpp from request
        $this->updatePaging($request);

        $tag = urldecode($tag);

        $threads = Thread::getByTag(ucfirst($tag))
                    ->orderBy('created_at', 'ASC')
                    ->paginate($this->rpp);

        return view('threads.index')
                    ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'tag' => $tag, 'hasFilter' => $hasFilter])
                    ->with(compact('threads'));
    }

    /**
     * Display a listing of threads by series.
     *
     * @param $tag
     *
     * @return Response
     */
    public function indexSeries(Request $request, $tag)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        $tag = urldecode($tag);

        $threads = Thread::getBySeries(ucfirst($tag))
                    ->orderBy('created_at', 'ASC')
                    ->paginate($this->rpp);

        return view('threads.index')
                    ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'tag' => $tag, 'hasFilter' => $this->hasFilter])
                    ->with(compact('threads'));
    }

    /**
     * Display a listing of threads by entity.
     */
    public function indexRelatedTo(Request $request, string $slug): View
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        $tag = urldecode($slug);

        $threads = Thread::getByEntity(ucfirst($tag))
                    ->orderBy('created_at', 'ASC')
                    ->paginate($this->rpp);

        return view('threads.index')
                    ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'tag' => $tag, 'hasFilter' => $this->hasFilter])
                    ->with(compact('threads'));
    }

    public function create(): View
    {
        $threadCategories = ['' => ''] + ThreadCategory::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $series = Series::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $events = ['' => ''] + Event::orderBy('name', 'ASC')->pluck('slug', 'id')->all();

        return view('threads.create', compact('threadCategories', 'visibilities', 'tags', 'entities', 'series', 'events'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ThreadRequest|Request $request
     */
    public function store(ThreadRequest $request): RedirectResponse
    {
        $msg = '';

        // get the request
        $input = $request->all();

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();
                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;

                $msg .= ' Linked tag ' . $tag . '.';
            }
        }

        $thread = Thread::create($input);

        $thread->tags()->attach($syncArray);
        $thread->entities()->attach($request->input('entity_list'));
        $thread->series()->attach($request->input('series_list'));

        // here, make a call to notify all users who are following any of the sync'd tags
        $this->notifyFollowing($thread);

        // add to activity log
        Activity::log($thread, $this->user, 1);

        flash()->success('Success', 'Your thread has been created. ' . $msg);

        return redirect()->route('threads.show', compact('thread'));
    }

    protected function notifyFollowing(Thread $thread): RedirectResponse
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $thread->tags()->get();
        $users = [];

        // notify users following any tags related to the thread
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $thread, $tag, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $tag->name . ' :: ' . $thread->created_at->format('D F jS') . ' ' . $thread->name);
                    });
                    $users[$user->id] = $tag->name;
                }
            }
        }

        // notify users following any of the series
        $series = $thread->series()->get();

        foreach ($series as $s) {
            foreach ($s->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $s, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $thread, $s, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $s->name . ' :: ' . $thread->created_at->format('D F jS') . ' ' . $thread->name);
                    });
                    $users[$user->id] = $s->name;
                }
            }
        }

        return back();
    }

    /**
     * Create a conversation slug.
     */
    public function makeSlugFromTitle(string $title): string
    {
        $slug = Str::slug($title);

        $count = Thread::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function show(Thread $thread): View
    {
        // TODO if the gate does not allow this user to show a forum redirect to home

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        // call a log for this and prevent it from going out of control
        ++$thread->views;
        $thread->save();

        return view('threads.show', compact('thread', 'tags'));
    }

    public function lock(int $id): RedirectResponse
    {
        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }
        // call a log for this and prevent it from going out of control
        $thread->locked_by = $this->user->id;
        $thread->locked_at = Carbon::now();

        $thread->save();

        // add to activity log
        Activity::log($thread, $this->user, 8);

        return back();
    }

    public function unlock(int $id): RedirectResponse
    {
        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // call a log for this and prevent it from going out of control
        $thread->locked_by = null;
        $thread->locked_at = null;
        $thread->save();

        // add to activity log
        Activity::log($thread, $this->user, 9);

        return back();
    }

    public function edit(Thread $thread): View
    {
        $this->middleware('auth');

        $threadCategories = ['' => ''] + ThreadCategory::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $visibilities = ['' => ''] + Visibility::pluck('name', 'id')->all();
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $series = Series::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $events = ['' => ''] + Event::orderBy('name', 'ASC')->pluck('slug', 'id')->all();

        return view('threads.edit', compact('thread', 'threadCategories', 'visibilities', 'tags', 'entities', 'series', 'events'));
    }

    public function update(Thread $thread, ThreadRequest $request): RedirectResponse
    {
        $msg = '';

        $thread->fill($request->input())->save();

        if (!$thread->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();
                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $thread->tags()->sync($syncArray);
        $thread->entities()->sync($request->input('entity_list', []));
        $thread->series()->sync($request->input('series_list', []));

        // add to activity log
        Activity::log($thread, $this->user, 2);

        flash('Success', 'Your thread has been updated');

        return redirect('threads');
    }

    protected function unauthorized(ThreadRequest $request)
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @internal param int $id
     */
    public function destroy(
        Thread $thread,
        Request $request
    ) {
        $this->authorize('update', $thread);

        // if not created by the user or super_admin
        if ((null !== $thread->user) && ($thread->created_by !== auth()->id()) && !$this->user->hasGroup('super_admin')) {
            if ($request->wantsJson()) {
                return response(['status' => 'Permission Denied'], 403);
            }

            return redirect('/login');
        }

        // add to activity log
        Activity::log($thread, $this->user, 3);

        $thread->delete();

        flash()->success('Success', 'Your thread has been deleted!');

        return redirect('threads');
    }

    public function follow(int $id, Request $request): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = 'thread'; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $follow->save();

        Log::info('User ' . $id . ' is following ' . $thread->name);

        flash()->success('Success', 'You are now following the thread - ' . $thread->name);

        return back();
    }

    public function unfollow(int $id, Request $request): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // delete the follow
        $response = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'thread')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer following the thread.');

        return back();
    }

    public function like(int $id, Request $request): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // add the following response
        $like = new Like();
        $like->object_id = $id;
        $like->user_id = $this->user->id;
        $like->object_type = 'thread';
        $like->save();

        // update the likes
        ++$thread->likes;
        $thread->save();

        Log::info('User ' . $id . ' is liking ' . $thread->name);

        flash()->success('Success', 'You are now liking the thread - ' . $thread->name);

        return back();
    }

    /**
     * Mark user as unliking the thread.
     *
     * @param int $id
     *
     * @throws \Exception
     */
    public function unlike(int $id): RedirectResponse
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$thread = Thread::find($id)) {
            flash()->error('Error', 'No such thread');

            return back();
        }

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'thread')->first();
        $response->delete();

        // update the likes
        --$thread->likes;
        $thread->save();

        flash()->success('Success', 'You are no longer liking the thread.');

        return back();
    }

    /**
     * Returns true if the user has any filters outside of the default.
     *
     * @return bool
     */
    protected function getIsFiltered(Request $request): bool
    {
        if (($filters = $this->getFilters($request)) === $this->getDefaultFilters()) {
            return false;
        }

        return (bool) count($filters);
    }

    /**
     * Get session filters.
     *
     * @return array
     */
    public function getFilters(Request $request): array
    {
        return $this->getAttribute($request, 'filters', $this->getDefaultFilters());
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getAttribute(Request $request, string $attribute, $default = null)
    {
        return $request->session()
            ->get($this->prefix . $attribute, $default);
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
}
