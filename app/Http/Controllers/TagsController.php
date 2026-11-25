<?php

namespace App\Http\Controllers;

use App\Filters\TagFilters;
use App\Http\Requests\TagRequest;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Series;
use App\Models\Tag;
use App\Models\TagType;
use App\Models\Visibility;
use App\Services\StringHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TagsController extends Controller
{
    /**
     * Options for the signed-in user's "Display Type" filter on the tag grid.
     */
    public const DISPLAY_TYPES = [
        'all' => 'All Tags',
        'following' => 'Tags Followed',
        'not_following' => 'Tags Not Followed',
        'created' => 'Tags Created',
        'not_created' => 'Tags Not Created',
    ];

    protected Tag $tag;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

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
        // default to no tag
        $tag = null;

        // the signed-in user's followed tags, for the follow buttons of the grid
        $userTags = $this->user?->getTagsFollowing();

        return view('tags.index-tw')
                ->with(array_merge(
                    compact('tag', 'userTags'),
                    $this->tagGrid($request),
                    $this->getListControlOptions()
                ));
    }

    /**
     * The searchable, sortable grid of every tag, and its list controls.
     *
     * @return array<string, mixed>
     */
    private function tagGrid(Request $request): array
    {
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        $limit = (int) $request->input('limit', $this->defaultLimit);

        if (!in_array($sort, array_keys($this->getListControlOptions()['sortOptions']))) {
            $sort = 'name';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $search = $request->input('search', '');

        $tagFilters = new TagFilters($request);
        $filters = $request->input('filters', []);
        $filters = $tagFilters->normalizeFilters(is_array($filters) ? $filters : []);

        $query = Tag::query()
            ->when(!empty($search), fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->withGridThumbnail()
            ->when($sort === 'events_count', fn ($q) => $q->withCount(['events' => fn ($e) => $e->where('events.visibility_id', Visibility::VISIBILITY_PUBLIC)]))
            ->orderBy($sort, $direction);

        $tags = $tagFilters->applyFilters($query, $filters)
            ->paginate($limit)
            ->appends(['search' => $search, 'filters' => $filters, 'sort' => $sort, 'direction' => $direction, 'limit' => $limit]);

        $displayType = $filters['display_type'] ?? 'all';
        $hasDisplayType = $this->user && $displayType !== 'all' && isset(self::DISPLAY_TYPES[$displayType]);

        return [
            'tags' => $tags,
            'search' => $search,
            'filters' => $filters,
            'displayTypes' => self::DISPLAY_TYPES,
            'hasFilter' => !empty($search) || $hasDisplayType,
            'sort' => $sort,
            'direction' => $direction,
            'limit' => $limit,
        ];
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
        $input = $request->validated();

        // if the tag name does not exist, create
        if (!Tag::where('name', '=', $input['name'])->first()) {
            // set the slug
            $input['slug'] = Str::slug($input['name'], '-');
            $tag = $tag->create($input);

            flash()->success('Success', sprintf('You added a new tag %s.', $tag->name));

            // add to activity log
            Activity::log($tag, $this->user, 1);

            return redirect()->route('tags.show', ['tag' => $tag->slug]);
        }

        flash()->error('Error', sprintf('The tag %s already exists.', $input['name']));

        return back()->withInput();
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

        // add the following response; a double submit must not add a second row (#2341)
        $follow = Follow::firstOrCreate(['object_id' => $tag->id, 'user_id' => $this->user->id, 'object_type' => $type]);

        if ($follow->wasRecentlyCreated) {
            Log::info('User '.$id.' is following '.$object->name);

            // add to activity log
            Activity::log($tag, $this->user, 6);
        }

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

        // delete the follow, including any duplicates left by older double submits
        if (Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', $type)->delete()) {
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
     * Update the specified resource in storage.
     */
    public function update(Tag $tag, TagRequest $request): RedirectResponse
    {
        $this->authorize('update', $tag);

        $msg = '';

        $input = $request->validated();

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
