<?php namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Requests\EntityRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use App\Entity;
use App\EntityFilters;
use App\EntityType;
use App\EntityStatus;
use App\Tag;
use App\Alias;
use App\Role;
use App\Photo;
use App\Follow;

class GenericObjectController extends Controller {

	public function __construct(Entity $entity)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);

        // default list variables
        $this->rpp = 5;
        $this->page = 1;
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
		parent::__construct();
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
            $this->sortOrder = $request->input('sort_direction');
        };

        // set results per page
        if ($request->input('rpp')) {
            $this->rpp = $request->input('rpp');
        };
    }


    abstract protected function baseCriteria();

	/**
	 * Reset the filtering of entities
	 *
	 * @return Response
	 */
	public function reset()
	{
	    $query = $this->baseCriteria();
 		$objects = $query->get();

		return view('index', compact('objects'));
	}



	/**
	 * Add a photo to an entity
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function addPhoto($id, Request $request)
	{

		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

		//$photo = Photo::fromForm($request->file('file'));
		$photo = $this->makePhoto($request->file('file'));
		$photo->save();

		// attach to entity
		$entity = Entity::find($id);
		$entity->addPhoto($photo);
	}
	
	protected function makePhoto(UploadedFile $file)
	{
		return Photo::named($file->getClientOriginalName())
			->move($file);
	}


    /**
     * Returns true if the user has any filters outside of the default
     *
     * @return Boolean
     */
    protected function getIsFiltered(Request $request)
    {
        if (($filters = $this->getFilters($request)) == $this->getDefaultFilters()) {
            return false;
        }
        return (bool)count($filters);
    }


    /**
     * Gets the reporting options from the request and saves to session
     *
     * @param Request $request
     */
    public function getReportingOptions(Request $request)
    {
        foreach (array('page', 'rpp', 'sort', 'criteria') as $option) {
            if (!$request->has($option)) {
                continue;
            }
            switch ($option) {
                case 'sort':
                    $value = array
                    (
                        $request->input($option),
                        $request->input('sort_order', 'asc'),
                    );
                    break;
                default:
                    $value = $request->input($option);
                    break;
            }
            call_user_func
            (
                array($this, sprintf('set%s', ucwords($option))),
                $value
            );
        }
    }

    /**
     * Get user session attribute
     *
     * @param String $attribute
     * @param Mixed $default
     * @param Request $request
     * @return Mixed
     */
    public function getAttribute($attribute, $default = null, Request $request)
    {
        return $request->session()
            ->get($this->prefix . $attribute, $default);
    }

    /**
     * Get session filters
     *
     * @return Array
     */
    public function getFilters(Request $request)
    {
        return $this->getAttribute('filters', $this->getDefaultFilters(), $request);
    }

    /**
     * Criteria provides a way to define criteria to be applied to a tab on the index page.
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Get the current page for this module
     *
     * @return integner
     */
    public function getPage()
    {
        return $this->getAttribute('page', 1);
    }

    /**
     * Get the current results per page
     *
     * @param Request $request
     * @return integer
     */
    public function getRpp(Request $request)
    {
        return $this->getAttribute('rpp', $this->rpp);
    }

    /**
     * Get the sort order and column
     *
     * @return array
     */
    public function getSort(Request $request)
    {
        return $this->getAttribute('sort', $this->getDefaultSort());
    }


    /**
     * Get the default sort array
     *
     * @return Array
     */
    public function getDefaultSort()
    {
        return array('id', 'desc');
    }


    /**
     * Get the default filters array
     *
     * @return Array
     */
    public function getDefaultFilters()
    {
        return array();
    }

    /**
     * Set user session attribute
     *
     * @param String $attribute
     * @param Mixed $value
     * @param Request $request
     * @return Mixed
     */
    public function setAttribute($attribute, $value, Request $request)
    {
        return $request->session()->set($this->prefix . $attribute, $value);
    }

    /**
     * Set filters attribute
     *
     * @param array $input
     * @return array
     */
    public function setFilters(Request $request, array $input)
    {
        return $this->setAttribute('filters', $input, $request);
    }

    /**
     * Set criteria.
     *
     * @param array $input
     * @return string
     */
    public function setCriteria($input)
    {
        $this->criteria = $input;
        return $this->criteria;
    }

    /**
     * Set page attribute
     *
     * @param integer $input
     * @return integer
     */
    public function setPage($input)
    {
        return $this->setAttribute('page', $input);
    }

    /**
     * Set results per page attribute
     *
     * @param integer $input
     * @return integer
     */
    public function setRpp($input)
    {
        return $this->setAttribute('rpp', 5);
    }

    /**
     * Set sort order attribute
     *
     * @param array $input
     * @return array
     */
    public function setSort(array $input)
    {
        return $this->setAttribute('sort', $input);
    }

}
