<?php namespace App\Http\Controllers;

use App\Activity;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntityTypeRequest;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\EntityType;
use App\Http\Requests\EntityRequest;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class EntityTypesController extends Controller {

	protected int $rpp;
    protected int $page;
    protected array $sort;
    protected string $sortBy;
    protected string $sortOrder;
    protected array $defaultCriteria;
    protected bool $hasFilter;
	protected string $prefix;
	
	public function __construct()
	{
		$this->middleware('auth', ['except' => array('index', 'show')]);

			// prefix for session storage
			$this->prefix = 'app.entity-types.';

			// default list variables
			$this->rpp = 10;
			$this->page = 1;
			$this->sort = array('name', 'desc');
			$this->sortBy = 'created_at';
			$this->sortOrder = 'desc';
			$this->defaultCriteria = [];
			$this->hasFilter = 1;
			parent::__construct();
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request)
	{
        // base criteria
        $query = $this->buildCriteria($request);

        // get the threads
        $entityTypes = $query->paginate($this->rpp);

		return view('entityTypes.index')
		->with(['entityTypes' => $entityTypes, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder])
		->withCompact('entityTypes');
	}

    /**
     * Builds the criteria from the session.
     *
     * @return $query
     */
    public function buildCriteria(Request $request): Builder
    {

        // base criteria
        $query = EntityType::orderBy($this->sortBy, $this->sortOrder);


        return $query;
    }

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('entityTypes.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	 public function store(Request $request, EntityType $entityType)
 	{
 		$input = $request->all();

 		$entityType->create($input);

 		return redirect()->route('entity-types.index');
 	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show(EntityType $entityType)
	{
		return view('entityTypes.show', compact('entityType'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit(EntityType $entityType)
	{
		$this->middleware('auth');

		return view('entityTypes.edit', compact('entityType'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(EntityType $entityType, Request $request)
	{
		$entityType->fill($request->input())->save();

		return redirect('entity-types');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy(EntityType $entityType)
	{
		$name = $entityType->name;

		try {
			$entityType->delete();
		} catch (Exception $e) {
			Log::error(sprintf('Could not delete the entity type %s', $name));
		};

        // add to activity log
        Activity::log($entityType, $this->user, 3);

        flash()->success('Success', sprintf('Your entity type %s has been deleted!', $name));

		return redirect('entity-types');
	}

}
