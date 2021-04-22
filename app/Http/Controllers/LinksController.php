<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Entity;
use App\Models\Link;
use App\Models\Visibility;

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

    protected $rules = [
        'text' => ['required', 'min:3'],
        'url' => ['required', 'min:3'],
    ];

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

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
     * Display a listing of the resource.
     *
     * @param  Entity 		$entity
     * @return Response
     */
    public function index(Entity $entity)
    {
        return view('links.index', compact('entity'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Entity 		$entity
     * @return Response
     */
    public function create(Entity $entity)
    {
        return view('links.create', compact('entity'))
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request 			$request
     * @param  Entity 		$entity
     * @return Response
     */
    public function store(Request $request, Entity $entity)
    {
        $msg = '';

        // get the request
        $input = $request->all();
        $input['entity_id'] = $entity->id;

        $this->validate($request, $this->rules);

        $link = Link::create($input);

        $entity->links()->attach($link->id);

        flash()->success('Success', 'Your link has been created');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Display the specified resource.
     *
     * @param  Entity 		$entity
     * @param  Link     	$link
     * @return Response
     */
    public function show(Entity $entity, Link $link)
    {
        return view('links.show', compact('entity', 'link'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Entity 		$entity
     * @param  Link  	        $link
     * @return Response
     */
    public function edit(Entity $entity, Link $link)
    {
        return view('links.edit', compact('entity', 'link'))
            ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request 		$request
     * @param  Entity 		$entity
     * @param  Link     	$link
     * @return Response
     */
    public function update(Request $request, Entity $entity, Link $link)
    {
        $link->fill($request->input())->save();

        flash()->success('Success', 'Your link has been updated!');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Entity $entity
     * @param  Link $link
     * @return Response
     * @throws \Exception
     */
    public function destroy(Entity $entity, Link $link)
    {
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
}
