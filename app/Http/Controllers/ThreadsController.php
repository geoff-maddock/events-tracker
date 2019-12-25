<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Entity;
use App\Event;
use App\Follow;
use App\Http\Requests\ThreadRequest;
use App\Like;
use App\Series;
use App\Tag;
use App\Thread;
use App\ThreadCategory;
use App\Visibility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ThreadsController extends Controller
{
    // define a list of variables
    protected $prefix;
    protected $rpp;
    protected $page;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $hasFilter;
    protected $filters;
    protected $criteria;

    public function __construct(Thread $thread)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);
        $this->thread = $thread;

        // prefix for session storage
        $this->prefix = 'app.threads.';

        // default list variables - move to function that set from session or default
        $this->rpp = 10;
        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = null;
        $this->hasFilter = 1;

        parent::__construct();
    }

    /**
     * Criteria provides a way to define criteria to be applied to a tab on the index page.
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Get the current page for this module.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->getAttribute('page', 1);
    }

    /**
     * Set page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    public function setPage($input)
    {
        return $this->setAttribute('page', $input);
    }

    /**
     * Set user session attribute.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function setAttribute(string $attribute, $value, Request $request)
    {
        return $request->session()
            ->put($this->prefix.$attribute, $value);
    }

    /**
     * Get the current results per page.
     *
     * @return int
     */
    public function getRpp(Request $request)
    {
        return $this->getAttribute('rpp', $this->rpp);
    }

    /**
     * Set results per page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    public function setRpp($input)
    {
        return $this->setAttribute('rpp', 5);
    }

    /**
     * Get the sort order and column.
     *
     * @return array
     */
    public function getSort(Request $request)
    {
        return $this->getAttribute('sort', $this->getDefaultSort());
    }

    /**
     * Set sort order attribute.
     *
     * @return array
     */
    public function setSort(array $input)
    {
        return $this->setAttribute('sort', $input);
    }

    /**
     * Get the default sort array.
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return ['id', 'desc'];
    }

    /**
     * Set criteria.
     *
     * @param array $input
     *
     * @return string
     */
    public function setCriteria($input)
    {
        $this->criteria = $input;

        return $this->criteria;
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     *
     * @throws \Throwable
     */
    public function index(Request $request)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        // initialize the query
        $query = $this->buildCriteria($request);

        // get the threads
        $threads = $query->with('visibility')->paginate($this->rpp);

        // filter only public threads or those created by the logged in user
        $threads->filter(function ($e) {
            return ('Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        // if the user is not authenticated, filter out any guarded threads
        if (!Auth::check()) {
            $threads->filter(function ($e) {
                return 'Guarded' != $e->visibility->name;
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
                        'filters' => $filters,
                    ])->render();
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
     * Builds the criteria from the session.
     *
     * @return $this $query
     */
    public function buildCriteria(Request $request)
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
            $query->where('name', 'like', '%'.$name.'%');
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
        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // update filters based on the request input
        $this->setFilters($request, array_merge($this->getFilters($request), $request->input()));

        // get the merged filters
        $this->filters = $this->getFilters($request);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // flag that there are filters
        $this->hasFilter = count($this->filters);

        // get the criteria given the request (could pass filters instead?)
        $query = $this->buildCriteria($request);

        // get threads
        $threads = $query->paginate($this->rpp);
        $threads->filter(function ($e) {
            return ($e->visibility && 'Public' == $e->visibility->name) || ($this->user && $e->created_by == $this->user->id);
        });

        return view('threads.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter, 'filters' => $this->filters,
            ])
            ->with(compact('threads'));
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
     * Reset the filtering of entities.
     *
     * @return Response
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
     *
     * @param $slug
     *
     * @return Response
     */
    public function indexRelatedTo(Request $request, $slug)
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
     *
     * @return \Illuminate\Http\Response
     */
    public function store(ThreadRequest $request, Thread $thread)
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

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;

                $msg .= ' Linked tag '.$tag.'.';
            }
        }

        $thread = $thread->create($input);

        $thread->tags()->attach($syncArray);
        $thread->entities()->attach($request->input('entity_list'));
        $thread->series()->attach($request->input('series_list'));

        // here, make a call to notify all users who are following any of the sync'd tags
        $this->notifyFollowing($thread);

        // add to activity log
        Activity::log($thread, $this->user, 1);

        flash()->success('Success', 'Your thread has been created. '.$msg);

        return redirect()->route('threads.show', compact('thread'));
    }

    /**
     * @param $thread
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($thread)
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
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $thread, $tag, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$thread->created_at->format('D F jS').' '.$thread->name);
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
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $s, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $thread, $s, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$s->name.' :: '.$thread->created_at->format('D F jS').' '.$thread->name);
                    });
                    $users[$user->id] = $s->name;
                }
            }
        }

        return back();
    }

    /**
     * Create a conversation slug.
     *
     * @param string $title
     *
     * @return string
     */
    public function makeSlugFromTitle($title)
    {
        $slug = Str::slug($title);

        $count = Thread::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function show(Thread $thread)
    {
        // if the gate does not allow this user to show a forum redirect to home

        // call a log for this and prevent it from going out of control
        ++$thread->views;
        $thread->save();

        return view('threads.show', compact('thread'));
    }

    /**
     * Lock the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function lock($id)
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

    /**
     * Unlock the specified resource.
     *
     * @param $id
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param Thread $thread
     */
    public function unlock($id)
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

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function edit(Thread $thread)
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

    /**
     * Update the specified resource in storage.
     *
     * @param ThreadRequest|Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function update(Thread $thread, ThreadRequest $request)
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

                $msg .= ' Added tag '.$tag.'.';
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

        \Session::flash('flash_message', 'Not authorized');

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
    public function destroy(Thread $thread,
                            Request $request)
    {
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

    /**
     * Mark user as following the thread.
     *
     * @param $id
     *
     * @return Response
     */
    public function follow($id, Request $request)
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

        Log::info('User '.$id.' is following '.$thread->name);

        flash()->success('Success', 'You are now following the thread - '.$thread->name);

        return back();
    }

    /**
     * Mark user as unfollowing the thread.
     *
     * @param $id
     *
     * @return Response
     */
    public function unfollow($id, Request $request)
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

    /**
     * Mark user as liking the thread.
     *
     * @param $id
     *
     * @return Response
     */
    public function like($id, Request $request)
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

        Log::info('User '.$id.' is liking '.$thread->name);

        flash()->success('Success', 'You are now liking the thread - '.$thread->name);

        return back();
    }

    /**
     * Mark user as unliking the thread.
     *
     * @param $id
     *
     * @return Response
     */
    public function unlike($id, Request $request)
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
    protected function getIsFiltered(Request $request)
    {
        if (($filters = $this->getFilters($request)) == $this->getDefaultFilters()) {
            return false;
        }

        return (bool) count($filters);
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
}
