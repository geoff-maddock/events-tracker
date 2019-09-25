<?php namespace App\Http\Controllers;

use App\Filters\MenuFilters;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Visibility;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use App\Menu;


class MenusController extends Controller {

    protected $menu;
    protected $rpp;
    protected $sortBy;
    protected $sortDirection;

	public function __construct(Menu $menu)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update', 'content')]);
		$this->menu = $menu;

		// default list variables
		$this->rpp = 15;
		$this->sortBy = 'created_at';
		$this->sortDirection = 'desc';

		parent::__construct();
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request)
	{
 		// updates sort, rpp from request
 		$this->updatePaging($request);

        $menus = Menu::orderBy($this->sortBy, $this->sortDirection)->paginate($this->rpp);

        return view('menus.index')
        	->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortDirection' => $this->sortDirection])
        	->with(compact('menus'));
	}

	/**
	 * Update the page list parameters from the request
	 *
	 */
	protected function updatePaging($request)
	{
 		// set sort by column
 		if ($request->input('sort_by')) {
 			$this->sortBy = $request->input('sort_by');
 		};

		// set sort direction
 		if ($request->input('sort_direction')) {
 			$this->sortDirection = $request->input('sort_direction');
 		};

 		// set results per page
 		if ($request->input('rpp')) {
 			$this->rpp = $request->input('rpp');
 		};
	}

	/**
	 * Filter the list of menus
	 *
	 * @return Response
	 */
	public function filter(Request $request, MenuFilters $filters)
	{
		// refactor this to filter by an array of $filter params that contain all the passed filters

		$menus = $this->menu->active();

   		if ($request->input('filter_name'))
 		{
 			$name = $request->input('filter_name');
			$menus = Menu::where('name', $name)->get();
 		}

		return view('menus.index', compact('menus', 'tag', 'name'));
	}

	/**
	 * Reset the filtering of permissions
	 *
	 * @return Response
	 */
	public function reset()
	{
 		$menus = Menu::orderBy('name', 'ASC')
					->get();

		return view('menus.index', compact('menus'));
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
        $visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();
		$parents =  [''=>''] + Menu::orderBy('name','ASC')->pluck('name','id')->all();

		return view('menus.create', compact('visibilities', 'parents'));
	}

	/**
	 * Store a newly created resource in storage.
	 * @param MenuRequest $request
     * @param Menu $menu
     *
	 * @return Response
	 */
	 public function store(MenuRequest $request, Menu $menu)
 	{
 		$msg = "";
 		
 		$input = $request->all();

        $menu = $menu->create($input);

		flash()->success('Success', 'Your menu has been created');

 		return redirect()->route('menus.index');
 	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Menu $menu
	 * @return Response
	 */
	public function show(Menu $menu)
	{
		return view('menus.show', compact('menu'));
	}

    /**
     * Display the specified menu content
     *
     * @param  Menu $menu
     * @return Response
     */
    public function content(Menu $menu)
    {
        return view('menus.content', compact('menu'));
    }

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  Menu $menu
	 * @return Response
	 */
	public function edit(Menu $menu)
	{
		$this->middleware('auth');

        $visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();
        $parents =  [''=>''] + Menu::orderBy('name','ASC')->pluck('name','id')->all();


		return view('menus.edit', compact('menu', 'visibilities', 'parents'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  Menu $menu
     * @param  Request $request
	 * @return Response
	 */
	public function update(Menu $menu, Request $request)
	{
		$msg = '';
		
		$menu->fill($request->input())->save();

		return redirect('menus');
	}

    /**
     * Remove the specified resource from storage.
     *
     * @param  Menu $menu
     * @return Response
     * @throws \Exception
     */
	public function destroy(Menu $menu)
	{
		$menu->delete();

		return redirect('menus');
	}


}
