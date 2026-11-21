<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Events\EventPhotoAdded;
use App\Filters\PhotoFilters;
use Illuminate\Database\Eloquent\Builder;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Tag;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\StringHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhotosController extends Controller
{
    use BuildsListPages;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected int $defaultGridLimit;

    protected int $gridLimit;

    // this should be an array of filter values
    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected PhotoFilters $filter;

    protected int $defaultWindow;

    public function __construct(PhotoFilters $filter)
    {
        $this->middleware('auth', ['only' => ['destroy', 'setPrimary', 'unsetPrimary', 'setEvent', 'unsetEvent']]);

        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.photos.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultGridLimit = 24;
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultWindow = 4;

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->gridLimit = 24;

        $this->defaultSortCriteria = ['photos.created_at' => 'desc'];
        parent::__construct();
    }

    /**
     * Display a grid listing of the resource.
     *
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        return $this->photoListPage($listParamSessionStore, $listEntityResultBuilder, 'internal_photo_index', $this->photoQuery(), ['is_event' => 1]);
    }

    /**
     * Display a listing of photos ofevents by tag.
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug,
        StringHelper $stringHelper
    ): string {
        // get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $baseQuery = $this->photoQuery()->whereHas('events.tags', function ($q) use ($tag) {
            $q->where('slug', '=', $tag->slug);
        });

        return $this->photoListPage($listParamSessionStore, $listEntityResultBuilder, 'internal_photo_tags', $baseQuery, ['is_event' => 1], ['tag' => $tag]);
    }

    /**
     * Display a grid listing of the resource.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        return $this->photoListPage($listParamSessionStore, $listEntityResultBuilder, 'internal_photo_index', $this->photoQuery());
    }

    public function show(Photo $photo): View
    {
        // list only the photo's events the viewer may see
        $photo->load(['events' => fn ($q) => $q->visible($this->user)]);

        return view('photos.show-tw', compact('photo'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $photo = Photo::findOrFail($id);

        $this->authorize('delete', $photo);

        $photo->delete();

        flash('Success', 'Your photo has been deleted');

        return back();
    }

    public function setPrimary(int $id): RedirectResponse
    {
        $photo = Photo::findOrFail($id);

        $this->authorize('update', $photo);

        // get anything linked to this photo
        $users = $photo->users;

        foreach ($users as $user) {
            foreach ($user->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $entities = $photo->entities;
        foreach ($entities as $entity) {
            foreach ($entity->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $events = $photo->events;
        foreach ($events as $event) {
            foreach ($event->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $series = $photo->series;
        foreach ($series as $s) {
            foreach ($s->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $blogs = $photo->blogs;
        foreach ($blogs as $blog) {
            foreach ($blog->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            }
        }

        $photo->is_primary = 1;
        $photo->save();

        // An event that had no usable flyer may now have one (issue #2058).
        foreach ($events as $event) {
            EventPhotoAdded::dispatch($event, true);
        }

        flash('Success', 'The primary photo was updated.');

        return back();
    }

    public function unsetPrimary(int $id): RedirectResponse
    {
        $photo = Photo::findOrFail($id);

        $this->authorize('update', $photo);

        $photo->is_primary = 0;
        $photo->save();

        flash('Success', 'The primary photo was unset.');

        return back();
    }

    public function setEvent(int $id): RedirectResponse
    {
        $photo = Photo::findOrFail($id);

        $this->authorize('update', $photo);

        $photo->is_event = 1;
        $photo->save();

        flash('Success', 'The photo is labeled as related to the event');

        return back();
    }

    public function unsetEvent(int $id): RedirectResponse
    {
        $photo = Photo::findOrFail($id);

        $this->authorize('update', $photo);

        $photo->is_event = 0;
        $photo->save();

        flash('Success', 'The photo is no longer labeled as from the event.');

        return back();
    }

    protected function getFilterOptions(): array
    {
        return [
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
            'relatedOptions' => ['' => ''] + Entity::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['photos.name' => 'Name', 'photos.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    /**
     * Reset the filtering of entities.
     *
     * @return RedirectResponse|View
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ) {
        $this->clearList($listParamSessionStore, 'internal_photo', $request->get('key') ?? 'internal_photo_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'photos.index'));
    }

    /**
     * Photos, leaving out those of events the viewer can't see.
     *
     * @return Builder<Photo>
     */
    private function photoQuery(): Builder
    {
        return Photo::query()
            ->leftJoin('event_photo', 'event_photo.photo_id', '=', 'photos.id')
            ->leftJoin('events', 'events.id', '=', 'event_photo.event_id')
            ->where(fn ($q) => $q->whereNull('events.id')->orWhere(Event::visibleTo($this->user)))
            ->select('photos.*');
    }

    /**
     * The photo grid every list action here renders.
     *
     * @param Builder<Photo> $baseQuery
     * @param array<string, mixed> $defaultFilters
     * @param array<string, mixed> $extra view data on top of the list's own
     */
    private function photoListPage(
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $keyPrefix,
        Builder $baseQuery,
        array $defaultFilters = [],
        array $extra = [],
    ): string {
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_photo',
            $keyPrefix,
            action([PhotosController::class, 'index']),
            $this->filter,
            $baseQuery,
            ['photos.created_at' => 'desc'],
            defaultFilters: $defaultFilters,
        );

        // Eager-load each photo's events and their venue; the grid card (photos/cell-tw)
        // reads $photo->events->first()->venue per row, which otherwise fires an N+1
        // lookup on the entities table (the venue) for every photo (EVENTREPO-WD).
        $photos = $listResultSet->getList()
            ->with(['events' => fn ($q) => $q->visible($this->user)->with('venue')])
            ->paginate($listResultSet->getLimit());

        return view('photos.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet, extra: $extra))
            ->with(compact('photos'))
            ->render();
    }

    /**
     * Reset the limit, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_photo', $request->get('key') ?? 'internal_photo_index', filters: false);

        return redirect()->route('photos.index');
    }
}
