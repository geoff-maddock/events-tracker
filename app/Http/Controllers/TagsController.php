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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $this->defaultLimit = 25;

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
        $this->authorize('delete', $tag);

        $tag->delete();

        return redirect('tags');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
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

        // get sort/pagination parameters from request
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        $limit = (int) $request->input('limit', $this->defaultLimit);

        $allowedSorts = array_keys($this->getListControlOptions()['sortOptions']);
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // get a list of all tags with optional search filter
        $query = Tag::query();

        $search = $request->input('search', '');
        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $tags = $query->withGridThumbnail()
            ->when($sort === 'events_count', fn ($q) => $q->withCount('events'))
            ->orderBy($sort, $direction)
            ->paginate($limit)
            ->appends(['search' => $search, 'sort' => $sort, 'direction' => $direction, 'limit' => $limit]);

        $hasFilter = !empty($search);

        return view('tags.index-tw')
                ->with(array_merge(
                    compact('series', 'entities', 'events', 'tag', 'tags', 'userTags', 'latestTags', 'search', 'hasFilter'),
                    ['sort' => $sort, 'direction' => $direction, 'limit' => $limit],
                    $this->getListControlOptions()
                ));
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [10 => 10, 25 => 25, 50 => 50, 100 => 100],
            'sortOptions' => ['name' => 'Name', 'events_count' => 'Popularity', 'created_at' => 'Date Created', 'updated_at' => 'Last Updated'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    /**
     * Return all relevant data related to a tag.
     */
    public function show(string $slug, StringHelper $stringHelper): View
    {
        $tagObject = Tag::where('slug', '=', $slug)->first();

        // convert the slug to name?
        $tag = $stringHelper->SlugToName($slug);

        // get limited series linked to the tag (8 for preview)
        $series = Series::getByTag($slug)
            ->with(Series::CARD_EAGER_LOAD)
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->limit(8)
            ->get();

        // get limited events linked to the tag (16 upcoming, 8 past for preview)
        $eventsBase = fn () => Event::getByTag($slug)
            ->with(EventsController::cardEventEagerLoad($this->user))
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->visible($this->user);
            });

        $upcomingEvents = $eventsBase()
            ->future()
            ->orderBy('name', 'ASC')
            ->limit(16)
            ->get();

        $pastEvents = $eventsBase()
            ->past()
            ->orderBy('name', 'ASC')
            ->limit(8)
            ->get();

        // get limited entities linked to the tag (8 for preview)
        $entities = Entity::getByTag($slug)
            ->with(Entity::CARD_EAGER_LOAD)
            ->where(function ($query) {
                /* @phpstan-ignore-next-line */
                $query->active();
            })
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->limit(8)
            ->get();

        // Get related tags based on co-occurrence with events
        $relatedTags = $tagObject ? $tagObject->relatedTags() : [];

        return view('tags.show-tw', compact('series', 'entities', 'upcomingEvents', 'pastEvents', 'slug', 'tag', 'tagObject', 'relatedTags'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @internal param int $id
     */
    public function edit(Tag $tag): View
    {
        $this->authorize('update', $tag);

        $tagTypes = TagType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('tags.edit-tw', compact('tag', 'tagTypes'));
    }

    /**
     * Show a form to create a new tag.
     **/
    public function create(): View
    {
        $tagTypes = TagType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('tags.create-tw', compact('tagTypes'));
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
                'Success' => view('tags.link-tw')
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

        // delete the follow
        $response = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', $type)->first();
        if ($response) {
            $response->delete();

            // add to activity log
            Activity::log($tag, $this->user, 7);
        }

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the tag - '.$tag->name,
                'Success' => view('tags.link-tw')
                    ->with(compact('tag'))
                    ->render(),
            ];
        }

        flash()->success('Success', 'You are no longer following the '.$type.' '.$tag->name);

        return back();
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
     * Update the specified resource in storage.
     */
    public function update(Tag $tag, TagRequest $request): RedirectResponse
    {
        $this->authorize('update', $tag);

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
