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
        // get the request
        $input = $request->all();

        if (!$input['slug']) {
            $slug = Str::slug($input['name'], '-');
        } else {
            $slug = $input['slug'];
        }

        $tagObject = Tag::where('slug', '=', $slug)->first();

        // if the tag name does not exist, create
        if (!$tagObject) {
            $tagObject = $tag->create($input);

            flash()->success('Success', sprintf('You added a new tag %s.', $tagObject->name));

            // add to activity log
            Activity::log($tagObject, $this->user, 1);
        } else {
            // flash()->error('Error', sprintf('The tag %s already exists.', $input['name']));
        }

        return response()->json($tagObject);
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
    public function update(Tag $tag, Request $request): JsonResponse
    {
        $msg = '';

        $tag->fill($request->input())->save();

        return response()->json($tag);
    }
}
