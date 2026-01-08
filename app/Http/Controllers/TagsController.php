<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Series;
use App\Models\Tag;
use App\Models\TagType;
use App\Services\StringHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TagsController extends Controller
{
    protected Tag $tag;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected int $page;

    protected array $filters;

    protected bool $hasFilter;

    protected array $defaultSortCriteria;

    public function __construct(Tag $tag)
    {
        $this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->tag = $tag;

        // prefix for session storage
        $this->prefix = 'app.tags.';

        // default list variables
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultLimit = 10;

        // set list variables
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['name' => 'desc'];

        $this->hasFilter = false;

        parent::__construct();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @internal param int $id
     *
     * @throws \Exception
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect('tags');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // count the most common tags in the recent past
        $latestTags = Tag::withCount(['events' => function (Builder $query) {
            $query->where('events.start_at', '>', Carbon::now()->subMonths(3));
        }])
        ->orderBy('events_count', 'desc')
        ->paginate(6);

        // default to no tag
        $tag = null;

        // get the tags the user is following
        $userTags = null;
        $tagNames = [];

        // get a list of all the user's followed tags
        if (isset($this->user)) {
            $userTags = $this->user->getTagsFollowing();
            foreach ($userTags as $userTag) {
                /** @var \App\Models\Tag $userTag */
                $tagNames[] = $userTag->name;
            }
        }

        // get all series linked to the tag
        $series = Series::whereHas('tags', function ($q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        })->visible($this->user)
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->with('tags', 'entities', 'occurrenceType','occurrenceWeek','occurrenceDay')
            ->paginate();

        // get all the events linked to the tag
        $events = Event::whereHas('tags', function ($q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        })->visible($this->user)
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->with('visibility', 'venue','tags', 'entities','series','eventType','threads')
                    ->simplePaginate($this->limit);

        // get all entities linked to the tag
        $entities = Entity::whereHas('tags', function ($q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        }) ->active()
                ->orderBy('entity_type_id', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->with('tags', 'locations', 'roles')
                    ->simplePaginate($this->limit);

        // get a list of all tags
        $tags = Tag::orderBy('name', 'ASC')->paginate(20);

        return view('tags.index-tw', compact('series', 'entities', 'events', 'tag', 'tags', 'userTags', 'latestTags'));
    }

    /**
     * Show the application dataAjax.
     */
    public function dataAjax(Request $request): JsonResponse
    {
        $data = [];

        if ($request->has('q')) {
            $search = $request->q;
            $data = DB::table('tags')
                    ->select('id', 'name')
                    ->where('name', 'LIKE', "%$search%")
                    ->get();
        }

        return response()->json($data);
    }

    /**
     * Display a listing of events by tag.
     */
    public function indexTags(string $tag): View
    {
        $tag = urldecode($tag);

        // get all series linked to the tag
        $series = Series::getByTag(ucfirst($tag))
                    ->where(function ($query) {
                        /* @phpstan-ignore-next-line */
                        $query->visible($this->user);
                    })
                    ->orderBy('start_at', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->paginate();

        // get all the events linked to the tag
        $events = Event::getByTag(ucfirst($tag))
                    ->orderBy('start_at', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->simplePaginate($this->limit);

        $events->filter(function ($e) {
            return ($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id);
        });

        // get all entities linked to the tag
        $entities = Entity::getByTag(ucfirst($tag))
                    ->where(function ($query) {
                        $query->active()
                        ->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
                    })
                    ->orderBy('entity_type_id', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->simplePaginate($this->limit);

        $tags = Tag::orderBy('name', 'ASC')->get();

        return view('tags.index', compact('series', 'entities', 'events', 'tag', 'tags'));
    }

    /**
     * Return all relevant data related to a tag.
     */
    public function show(string $slug, StringHelper $stringHelper): View
    {
        $tagObject = Tag::where('slug', '=', $slug)->first();

        // convert the slug to name?
        $tag = $stringHelper->SlugToName($slug);

        // get all series linked to the tag
        $series = Series::getByTag($slug)
            ->with('visibility', 'venue','tags', 'entities','eventType','threads','occurrenceType','occurrenceWeek','occurrenceDay')
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->paginate();

        // get all the events linked to the tag
        $events = Event::getByTag($slug)
            ->with('visibility', 'venue','tags', 'entities','series','eventType','threads')
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'DESC')
            ->orderBy('name', 'ASC')
            ->simplePaginate($this->limit);

        // get all entities linked to the tag
        $entities = Entity::getByTag($slug)
            ->with('tags', 'events','entityType','locations','entityStatus','user')
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->active();
            })
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->simplePaginate($this->limit);

        $tags = Tag::orderBy('name', 'ASC')->get();

        return view('tags.index', compact('series', 'entities', 'events', 'slug', 'tag', 'tagObject', 'tags'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @internal param int $id
     */
    public function edit(Tag $tag): View
    {
        $this->middleware('auth');

        $tagTypes = TagType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('tags.edit', compact('tag', 'tagTypes'));
    }

    /**
     * Show a form to create a new tag.
     **/
    public function create(): View
    {
        $tagTypes = TagType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('tags.create', compact('tagTypes'));
    }

    /**
     * Store a newly created resource.
     *
     * @internal param TagRequest $request
     */
    public function store(TagRequest $request, Tag $tag): RedirectResponse
    {
        $msg = '';

        // get the request
        $input = $request->all();

        // if the tag name does not exist, create
        if (!Tag::where('name', '=', $input['name'])->first()) {
            // set the slug
            $input['slug'] = Str::slug($input['name'], '-');
            $tag = $tag->create($input);

            flash()->success('Success', sprintf('You added a new tag %s.', $tag->name));

            // add to activity log
            Activity::log($tag, $this->user, 1);
        } else {
            flash()->error('Error', sprintf('The tag %s already exists.', $input['name']));
        }

        return back();
    }

    /**
     * Mark user as following the tag.
     *
     * @throws \Throwable
     */
    public function follow(int $id, Request $request): RedirectResponse | array
    {
        $type = 'tag';

        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        // how can i derive this class from a string?
        if (!$object = call_user_func('App\\Models\\'.ucfirst($type).'::find', $id)) { // Tag::find($id))
            flash()->error('Error', 'No such '.$type);

            return back();
        }

        $tag = $object;

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = $type;
        $follow->save();

        Log::info('User '.$id.' is following '.$object->name);

        // add to activity log
        Activity::log($tag, $this->user, 6);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the tag - '.$object->name,
                'Success' => view('tags.link')
                    ->with(compact('tag'))
                    ->render(),
            ];
        }

        flash()->success('Success', 'You are now following the '.$type.' - '.$object->name);

        return back();
    }

    /**
     * Mark user as unfollowing the tag.
     *
     * @throws \Throwable
     */
    public function unfollow(int $id, Request $request): RedirectResponse | array
    {
        $type = 'tag';

        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$tag = Tag::find($id)) {
            flash()->error('Error', 'No such '.$type);

            return back();
        }

        // add to activity log
        Activity::log($tag, $this->user, 7);

        // delete the follow
        $response = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', $type)->first();
        $response->delete();

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the tag - '.$tag->name,
                'Success' => view('tags.link')
                    ->with(compact('tag'))
                    ->render(),
            ];
        }

        flash()->success('Success', 'You are no longer following the '.$type.' '.$tag->name);

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
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getAttribute(Request $request, $attribute, $default = null)
    {
        return $request->session()
            ->get($this->prefix.$attribute, $default);
    }

    /**
     * Get session filters.
     *
     * @return array
     */
    public function getFilters(Request $request)
    {
        return $this->getAttribute($request, 'filters', $this->getDefaultFilters());
    }

    /**
     * Get the current page for this module.
     *
     * @return int
     */
    public function getPage(Request $request): ?int
    {
        return $this->getAttribute($request, 'page', 1);
    }

    /**
     * Get the current results per page.
     *
     * @return int
     */
    public function getLimit(Request $request)
    {
        return $this->getAttribute($request, 'limit', $this->limit);
    }

    /**
     * Get the sort order and column.
     *
     * @return array
     */
    public function getSort(Request $request)
    {
        return $this->getAttribute($request, 'sort', $this->getDefaultSort());
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
     * Get the default filters array.
     *
     * @return array
     */
    public function getDefaultFilters()
    {
        return [];
    }

    /**
     * Set user session attribute.
     */
    public function setAttribute(Request $request, string $attribute, mixed $value): void
    {
        $request->session()->put($this->prefix.$attribute, $value);
    }

    /**
     * Set filters attribute.
     */
    public function setFilters(Request $request, array $input): void
    {
        $this->setAttribute($request, 'filters', $input);
    }

    /**
     * Set page attribute.
     */
    public function setPage(Request $request, int $input): void
    {
        $this->setAttribute($request, 'page', $input);
    }

    /**
     * Set results per page attribute.
     */
    public function setLimit(Request $request, int $input): void
    {
        $this->setAttribute($request, 'limit', 5);
    }

    /**
     * Set sort order attribute.
     */
    public function setSort(Request $request, array $input): void
    {
        $this->setAttribute($request, 'sort', $input);
    }

    /**
     * Builds the criteria from the session.
     */
    public function buildCriteria(Request $request): Builder
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = Tag::query();

        // add the criteria from the session
        // check request for passed filter values
        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%'.$name.'%');
            $filters['filter_name'] = $name;
        }

        // change this - should be separate
        if (!empty($filters['filter_limit'])) {
            $this->limit = $filters['filter_limit'];
        }

        return $query;
    }

    protected function notifyFollowing(?Tag $tag): RedirectResponse
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $users = [];

        foreach ($tag->followers() as $user) {
            // if the user hasn't already been notified, then email them
            if (!array_key_exists($user->id, $users)) {
                Mail::send('emails.following-thread', ['user' => $user, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $tag, $reply_email, $site) {
                    $m->from($reply_email, $site);

                    $m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$tag->created_at->format('D F jS'));
                });
                $users[$user->id] = $tag->name;
            }
        }

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Tag $tag, TagRequest $request): RedirectResponse
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('name', '-'));

        $tag->fill($input)->save();

        // if we got this far, it worked
        $msg = 'Updated tag. ';

        // add to activity log
        Activity::log($tag, $this->user, Action::UPDATE);

        // flash this message
        flash()->success('Success', $msg);

        return redirect()->route('tags.show', compact('tag'));
    }
}
