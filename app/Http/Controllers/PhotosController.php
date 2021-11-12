<?php

namespace App\Http\Controllers;

use App\Filters\PhotoFilters;
use App\Http\Controllers\Controller;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Photo;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Visibility;
use App\Models\Tag;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhotosController extends Controller
{
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

    protected bool $hasFilter;

    protected int $defaultWindow;

    public function __construct(PhotoFilters $filter)
    {
        // $this->middleware('auth', ['except' => ['index', 'show']]);

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
    }

    /**
     * Display a listing of the resource.
     */
    public function indexSimple()
    {
        $photos = Photo::get();

        return view('photos.index', compact('photos'));
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
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_photo');
        $listParamSessionStore->setKeyPrefix('internal_photo_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PhotosController::class, 'index']));

        // set the default filter as is_event
        $defaultFilter = ['is_event' => 1];

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Photo::query()->select('photos.*');

        $listEntityResultBuilder
        ->setFilter($this->filter)
        ->setQueryBuilder($baseQuery)
        ->setDefaultFilters($defaultFilter)
        ->setDefaultSort(['photos.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the photos
        $photos = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('photos.index')
        ->with(array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters()
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
        ->with(compact('photos'))
        ->render();
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
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_photo');
        $listParamSessionStore->setKeyPrefix('internal_photo_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PhotosController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Photo::query()->select('photos.*');

        $listEntityResultBuilder
        ->setFilter($this->filter)
        ->setQueryBuilder($baseQuery)
        ->setDefaultSort(['photos.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the photos
        $photos = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('photos.index')
        ->with(array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters()
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
        ->with(compact('photos'))
        ->render();
    }

    /**
     * Show a form to create a new Article.
     **/
    public function create()
    {
        $tags = Tag::pluck('name', 'id');
        $entities = Entity::pluck('name', 'id');

        return view('photos.create', compact('tags', 'entities'));
    }

    public function show(Photo $photo)
    {
        return view('photos.show', compact('photo'));
    }

    public function store(Request $request, Photo $photo)
    {
        $input = $request->all();

        $photo = $photo->create($input);

        $photo->entities()->attach($request->input('entity_list'));

        Session::flash('flash_message', 'Your photo has been created!');

        return redirect()->route('photos.index');
    }

    public function edit(Photo $photo)
    {
        $this->middleware('auth');

        $type = EntityType::where('name', 'Venue')->first();
        $venues = array_merge(['' => ''], DB::table('entities')->where('entity_type_id', $type->id)->orderBy('name', 'ASC')->pluck('name', 'id'));
        $visibilities = array_merge(['' => ''], Visibility::pluck('name', 'id'));
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id');
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id');

        return view('photos.edit', compact('photo', 'venues', 'visibilities', 'tags', 'entities'));
    }

    public function update(Photo $photo, Request $request)
    {
        $photo->fill($request->input())->save();

        $photo->entities()->sync($request->input('entity_list', []));

        \Session::flash('flash_message', 'Your photo has been updated!');

        return redirect('photos');
    }

    public function destroy($id)
    {
        $photo = Photo::findOrFail($id)->delete();

        flash('Success', 'Your photo has been deleted');

        return back();
    }

    public function setPrimary($id)
    {
        $photo = Photo::findOrFail($id);

        // get anything linked to this photo
        $users = $photo->users;

        foreach ($users as $user) {
            foreach ($user->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $entities = $photo->entities;
        foreach ($entities as $entity) {
            foreach ($entity->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $events = $photo->events;
        foreach ($events as $event) {
            foreach ($event->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $series = $photo->series;
        foreach ($series as $s) {
            foreach ($s->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $photo->is_primary = 1;
        $photo->save();

        flash('Success', 'The primary photo was updated.');

        return back();
    }

    public function unsetPrimary($id)
    {
        $photo = Photo::findOrFail($id);

        $photo->is_primary = 0;
        $photo->save();

        flash('Success', 'The primary photo was unset.');

        return back();
    }

    public function setEvent($id)
    {
        $photo = Photo::findOrFail($id);

        $photo->is_event = 1;
        $photo->save();

        flash('Success', 'The photo is labeled as related to the event');

        return back();
    }

    public function unsetEvent($id)
    {
        $photo = Photo::findOrFail($id);

        $photo->is_event = 0;
        $photo->save();

        flash('Success', 'The photo is no longer labeled as from the event.');

        return back();
    }

    protected function getFilterOptions(): array
    {
        return  [
        ];
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['photos.name' => 'Name', 'photos.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }

    /**
     * Reset the filtering of entities.
     *
     * @return RedirectResponse | View
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ) {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_photo_index';
        $listParamSessionStore->setBaseIndex('internal_photo');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'photos.index');
    }
}
