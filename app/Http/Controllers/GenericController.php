<?php

namespace App\Http\Controllers;

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

class GenericController extends Controller
{
    protected $prefix;

    protected $rpp;

    protected $page;

    protected $sort;

    protected $sortBy;

    protected $sortOrder;

    protected $sortDirection;

    protected $defaultCriteria;

    protected $hasFilter;

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // default list variables
        $this->rpp = 5;
        $this->page = 1;
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        parent::__construct();
    }

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
     * Get the base criteria.
     */
    protected function baseCriteria()
    {
        return Entity::query();
    }

    //abstract protected function baseCriteria();

    /**
     * Add a photo to an entity
     *
     * @param  int $id
     * @param Request $request
     * @return void
     */
    public function addPhoto($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif'
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
     * Get the current page for this module
     *
     * @return integner
     */
    public function getPage(Request $request)
    {
        return $this->getAttribute($request, 'page', 1);
    }

    /**
     * Set page attribute
     *
     * @param integer $input
     * @return integer
     */
    public function setPage(Request $request, $input)
    {
        return $this->setAttribute($request, 'page', $input);
    }

    /**
     * Get the current results per page
     *
     * @param Request $request
     * @return integer
     */
    public function getRpp(Request $request)
    {
        return $this->getAttribute($request, 'rpp', $this->rpp);
    }

    /**
     * Set results per page attribute
     *
     * @param integer $input
     * @return integer
     */
    public function setRpp(Request $request, $input)
    {
        return $this->setAttribute($request, 'rpp', 5);
    }

    /**
     * Get the sort order and column
     *
     * @return array
     */
    public function getSort(Request $request)
    {
        return $this->getAttribute($request, 'sort', $this->getDefaultSort());
    }

    /**
     * Set sort order attribute
     *
     * @param array $input
     * @return array
     */
    public function setSort(Request $request, array $input)
    {
        return $this->setAttribute($request, 'sort', $input);
    }

    /**
     * Get the default sort array
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return ['id', 'desc'];
    }

    /**
     * Set filters attribute
     *
     * @param array $input
     * @return array
     */
    public function setFilters(Request $request, array $input)
    {
        return $this->setAttribute($request, 'filters', $input);
    }

    /**
     * Set user session attribute
     *
     * @param String $attribute
     * @param Mixed $value
     * @param Request $request
     * @return Mixed
     */
    public function setAttribute(Request $request, string $attribute, $value)
    {
        return $request->session()->put($this->prefix . $attribute, $value);
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
     * Get session filters
     *
     * @return Array
     */
    public function getFilters(Request $request)
    {
        return $this->getAttribute($request, 'filters', $this->getDefaultFilters());
    }

    /**
     * Get user session attribute
     *
     * @param String $attribute
     * @param Mixed $default
     * @param Request $request
     * @return Mixed
     */
    public function getAttribute(Request $request, string $attribute, $default = null)
    {
        return $request->session()
            ->get($this->prefix . $attribute, $default);
    }

    /**
     * Get the default filters array
     *
     * @return array
     */
    public function getDefaultFilters()
    {
        return [];
    }
}
