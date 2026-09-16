<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Link;
use App\Models\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LinksController extends Controller
{
    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected bool $hasFilter;

    protected array $rules = [
        'text' => ['required', 'min:3'],
        'url' => ['required', 'min:3'],
    ];

    public function __construct()
    {
        // per-entity ownership is checked in each action via EntityPolicy::update
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'text';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['links.text' => 'asc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;

        parent::__construct();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Entity $entity): View
    {
        $this->authorize('update', $entity);

        return view('links.create-tw', compact('entity'))
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('update', $entity);

        $msg = '';

        // get the request
        $input = $request->all();
        $input['entity_id'] = $entity->id;
        $input['is_primary'] = isset($input['is_primary']) ? 1 : 0;

        $this->validate($request, $this->rules);

        $link = Link::create($input);

        $entity->links()->attach($link->id);

        flash()->success('Success', 'Your link has been created');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity, Link $link): View
    {
        return view('links.show-tw', compact('entity', 'link'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity, Link $link): View
    {
        $this->authorize('update', $entity);
        $this->ensureBelongsToEntity($entity, $link);

        return view('links.edit-tw', compact('entity', 'link'))
            ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entity $entity, Link $link): RedirectResponse
    {
        $this->authorize('update', $entity);
        $this->ensureBelongsToEntity($entity, $link);

        $input = $request->all();
        $input['is_primary'] = isset($input['is_primary']) ? 1 : 0;

        $link->fill($input)->save();

        flash()->success('Success', 'Your link has been updated!');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity, Link $link): RedirectResponse
    {
        $this->authorize('update', $entity);
        $this->ensureBelongsToEntity($entity, $link);

        $link->delete();

        flash()->success('Success', 'Your link has been deleted!');

        return redirect()->route('entities.show', $entity->slug);
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilities' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
        ];
    }

    /**
     * A link reached through another entity's URL must not be editable by that entity's owner.
     */
    protected function ensureBelongsToEntity(Entity $entity, Link $link): void
    {
        abort_unless($entity->links()->whereKey($link->id)->exists(), 404);
    }
}
