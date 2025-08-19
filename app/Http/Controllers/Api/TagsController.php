<?php

namespace App\Http\Controllers\Api;

use App\Filters\TagFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagCollection;
use App\Http\Resources\TagResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Series;
use App\Models\Tag;
use App\Models\TagType;
use App\Services\SessionStore\ListParameterSessionStore;
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

    protected TagFilters $filter;

    public function __construct(Tag $tag, TagFilters $filter)
    {
        // $this->middleware('verified', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->tag = $tag;

        // prefix for session storage
        $this->prefix = 'app.tags.';

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['tags.name' => 'asc'];

        // set list variables
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['name' => 'desc'];

        $this->hasFilter = false;

        $this->filter = $filter;

        parent::__construct();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @internal param int $id
     *
     * @throws \Exception
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json([], 204);
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
        $listParamSessionStore->setBaseIndex('internal_tag');
        $listParamSessionStore->setKeyPrefix('internal_tag_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([TagsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Tag::query()
                        ->leftJoin('tag_types', 'tags.tag_type_id', '=', 'tag_types.id')
                        ->select('tags.*')
        ;

        // set the default filter to active
        // $defaultFilter = ['entity_status' => 'Active'];

        // TODO Change the query param requirements for filter and sort to use a simpler or more robust format

        // set the filter and base query
        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the tags
        $tags = $query->paginate($listResultSet->getLimit());

        // return view('tags.index', compact('series', 'entities', 'events', 'tag', 'tags', 'userTags', 'latestTags'));
        return response()->json(new TagCollection($tags));
    }

    /**
     * Display a listing of the most popular tags.
     */
    public function popular(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $limit = (int) $request->get('limit', 30);
        $from = Carbon::now()->subDays($days);

        $query = Tag::query()
            ->withCount([
                'events as events_count' => function ($q) use ($from) {
                    $q->where('start_at', '>=', $from);
                },
                'follows as follows_count' => function ($q) use ($from) {
                    $q->where('created_at', '>=', $from);
                },
            ])
            ->filter($this->filter);

        $tags = $query
            ->orderBy(DB::raw('events_count + follows_count'), 'desc')
            ->paginate($limit);

        $tags->getCollection()->transform(function ($tag) {
            $tag->popularity_score = $tag->events_count + $tag->follows_count;

            return $tag;
        });

        return response()->json(new TagCollection($tags));
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
    public function show(Tag $tag): JsonResponse
    {
        return response()->json(new TagResource($tag));
    }

    /**
     * Store a newly created resource.
     *
     * @internal param Request $request
     */
    public function store(Request $request, Tag $tag): JsonResponse
    {
        $input = $request->only(['name', 'slug', 'tag_type_id', 'description']);

        $input['slug'] = $input['slug'] ?? Str::slug($input['name'], '-');

        $tagObject = Tag::where('slug', '=', $input['slug'])->first();

        if (!$tagObject) {
            $tagObject = $tag->create($input);

            flash()->success('Success', sprintf('You added a new tag %s.', $tagObject->name));

            Activity::log($tagObject, $this->user, 1);
        }

        return response()->json(new TagResource($tagObject));
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
     * Follow the tag and return JSON.
     */
    public function followJson(Tag $tag, Request $request): JsonResponse
    {
        $user = $request->user();

        $follow = Follow::where('object_type', 'tag')
            ->where('object_id', $tag->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$follow) {
            $follow = new Follow();
            $follow->object_id = $tag->id;
            $follow->user_id = $user->id;
            $follow->object_type = 'tag';
            $follow->save();

            Activity::log($tag, $user, 6);
        }

        return response()->json(new TagResource($tag));
    }

    /**
     * Unfollow the tag and return JSON.
     */
    public function unfollowJson(Tag $tag, Request $request): JsonResponse
    {
        $user = $request->user();

        $follow = Follow::where('object_type', 'tag')
            ->where('object_id', $tag->id)
            ->where('user_id', $user->id)
            ->first();

        if ($follow) {
            $follow->delete();
            Activity::log($tag, $user, 7);
        }

        return response()->json([], 204);
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
    public function update(Tag $tag, Request $request): JsonResponse
    {
        $tag->fill($request->only(['name', 'slug', 'tag_type_id', 'description']))->save();

        return response()->json(new TagResource($tag));
    }
}
