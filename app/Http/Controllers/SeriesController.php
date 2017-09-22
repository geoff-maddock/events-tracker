<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\SeriesRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Mail;
use App\Series;
use App\Thread;
use App\EventType;
use App\Entity;
use App\OccurrenceDay;
use App\OccurrenceType;
use App\OccurrenceWeek;
use App\Tag;
use App\Visibility;
use App\Photo;
use App\Follow;

class SeriesController extends Controller {


	public function __construct(Series $series)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->series = $series;
		
        // prefix for session storage
        $this->prefix = 'app.series.';

        // default list variables
        $this->rpp = 100;
        $this->page = 1;
        $this->sort = array('name', 'desc');
        $this->sortBy = 'name';
        $this->sortOrder = 'asc';
        $this->defaultCriteria = NULL;
        $this->hasFilter = 0;
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

    /**
     * Apply the filters to the query
     *
     */
    protected function buildCriteria(array $filters = null)
    {
        if (is_null($filters)) {
            $filters = $this->getFilters();
        }
    }

    /**
     * Get the base criteria
     *
     */
    protected function baseCriteria()
    {
        $query = $this->series
            ->where('cancelled_at', NULL)
            ->orderBy('occurrence_type_id','ASC')
            ->orderBy('occurrence_week_id', 'ASC')
            ->orderBy('occurrence_day_id', 'ASC')
            ->orderBy('name', 'ASC');

        return $query;
    }

    /**
     * Filter the list of events
     *
     * @param Request $request
     * @return View
     * @internal param $Request
     */
    public function filter(Request $request)
    {
        $hasFilter = 1;

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // base criteria
        $query = $this->baseCriteria();

        // add the criteria from the session
        // check request for passed filter values

        if (!empty($request->input('filter_name')))
        {
            // getting name from the request
            $name = $request->input('filter_name');
            $query->where('name', 'like', '%'.$name.'%');
            // add to filters array
            $filters['filter_name'] = $name;
        }

        if (!empty($request->input('filter_occurrence_type')))
        {
            $type = $request->input('filter_occurrence_type');
            // add has clause
            $query->whereHas('occurrenceType',
                function($q) use ($type)
                {
                    $q->where('name','=', ucfirst($type));
                });

            // add to filters array
            $filters['filter_occurrence_type'] = $type;
        };

        if (!empty($request->input('filter_occurrence_week')))
        {
            $week = $request->input('filter_occurrence_week');
            // add has clause
            $query->whereHas('occurrenceWeek',
                function($q) use ($week)
                {
                    $q->where('name','=', ucfirst($week));
                });

            // add to filters array
            $filters['filter_occurrence_week'] = $week;
        };


        if (!empty($request->input('filter_occurrence_day')))
        {
            $day = $request->input('filter_occurrence_day');
            // add has clause
            $query->whereHas('occurrenceDay',
                function($q) use ($day)
                {
                    $q->where('name','=', ucfirst($day));
                });

            // add to filters array
            $filters['filter_occurrence_day'] = $day;
        };


        if (!empty($request->input('filter_tag')))
        {
            $tag = $request->input('filter_tag');
            $query->whereHas('tags', function($q) use ($tag)
            {
                $q->where('name','=', ucfirst($tag));
            });

            // add to filters array
            $filters['filter_tag'] = $tag;
        }


        // change this - should be seperate
        if (!empty($request->input('filter_rpp')))
        {
            $this->rpp = $request->input('filter_rpp');
        }

        // save filters to session
        $this->setFilters($request, $filters);

        // get series
        $series = $query->paginate($this->rpp);
        $series->filter(function ($e) {
            return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter,
                'filter_name' => isset($filters['filter_name']) ? $filters['filter_name'] : NULL,  // there should be a better way to do this...
                'filter_occurrence_type' => isset($filters['filter_occurrence_type']) ? $filters['filter_occurrence_type'] : NULL,
                'filter_occurrence_week' => isset($filters['filter_occurrence_week']) ? $filters['filter_occurrence_week'] : NULL,
                'filter_tag' => isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL
            ])
            ->with(compact('series'));
    }

    /**
     * Reset the filtering of entities
     *
     * @return Response
     */
    public function reset(Request $request)
    {
        // doesn't have filter, but temp
        $hasFilter = 1;

        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        // base criteria
        $query = $this->baseCriteria();

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get future events
        $series = $query->paginate($this->rpp);
        $series->filter(function ($e) {
            return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });


        return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('series'))
            ->render();

    }

	public function index()
	{
        $hasFilter = 1;

        // base criteria
        $query = $this->baseCriteria();

        $series = $query->paginate($this->rpp);

		$series = $series->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('series'))
            ->render();
	}

	public function indexCancelled()
	{
		$hasFilter = 1;

		$series = $this->series
		->whereNotNull('cancelled_at')
		->orderBy('occurrence_type_id','ASC')
		->orderBy('occurrence_week_id', 'ASC')
		->orderBy('occurrence_day_id', 'ASC')
		->orderBy('name', 'ASC')
		->get();

		$series = $series->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});


		return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('series'))
            ->render();
	}

	/**
	 * Display a listing of event series in a week view
	 *
	 * @return Response
	 */
	public function indexWeek()
	{
		$hasFilter = 1;

		$this->rpp = 5;

		// this is more complex because we want to show weeklies that fall on the days, plus monthlies that fall on the days
		// may be an iterative process that is called from the template to the series model that checks against each criteria and builds a list that way
		$series = Series::future()->get();
		return view('series.indexWeek')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('series'))
            ->render();
	}


	/**
	 * Display a listing of series related to entity
	 *
	 * @return Response
	 */
	public function indexRelatedTo($slug)
	{
		$hasFilter = 1;
 		$slug = urldecode($slug);

		$series = Series::getByEntity(strtolower($slug))
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('series','slug'))
            ->render();
	}

	/**
	 * Display a listing of events by tag
	 *
	 * @return Response
	 */
	public function indexTags($tag)
	{
 		$hasFilter = 1;
  		$tag = urldecode($tag);

		$series = Series::getByTag(ucfirst($tag))
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();
					

		return view('series.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('series','tag'))
            ->render();
	}

	/**
	 * Show a form to create a new series.
	 *
	 * @return view
	 **/

	public function create()
	{
		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->pluck('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->pluck('name', 'id')->all();

		$occurrenceTypes = [''=>''] + OccurrenceType::pluck('name', 'id')->all();
		$days = [''=>''] + OccurrenceDay::pluck('name', 'id')->all();
		$weeks = [''=>''] + OccurrenceWeek::pluck('name', 'id')->all();

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();

		return view('series.create', compact('venues','eventTypes','visibilities','tags','entities','promoters', 'weeks','days', 'occurrenceTypes'));
	}

	public function show(Series $series)
	{
		$events = $series->events()->paginate($this->rpp);
		$threads = $series->threads()->paginate($this->rpp);

		return view('series.show', compact('series','events','threads'));
	}


	public function store(SeriesRequest $request, Series $series)
	{
		$msg = "";
		$input = $request->all();

		$tagArray = $request->input('tag_list',[]);
		$syncArray = array();

		// check the elements in the tag list, and if any don't match, add the tag
		foreach ($tagArray as $key => $tag)
		{

			if (!DB::table('tags')->where('id', $tag)->get())
			{
				$newTag = new Tag;
				$newTag->name = ucwords(strtolower($tag));
				$newTag->tag_type_id = 1;
				$newTag->save();

				$syncArray[] = $newTag->id;

				$msg .= ' Added tag '.$tag.'.';
			} else {
				$syncArray[$key] = $tag;
			};
		}

		$s = $series->create($input);

		$s->tags()->attach($syncArray);
		$s->entities()->attach($request->input('entity_list'));

		flash()->success('Success', 'Your event template has been created');

		return redirect()->route('series.index');
	}

	public function edit(Series $series)
	{
		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->pluck('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->pluck('name', 'id')->all();

		$occurrenceTypes = [''=>''] + OccurrenceType::pluck('name', 'id')->all();
		$days = [''=>''] + OccurrenceDay::pluck('name', 'id')->all();
		$weeks = [''=>''] + OccurrenceWeek::pluck('name', 'id')->all();

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();


		return view('series.edit', compact('series','venues','eventTypes','visibilities','tags','entities','promoters', 'weeks','days', 'occurrenceTypes'));
	}



	public function createOccurrence(Request $request)
	{
		// create an event occurence based on the event template

		$series = Series::find($request->id);

		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->pluck('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->pluck('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->pluck('name', 'id')->all();

		$seriesList = [''=>''] + Series::orderBy('name','ASC')->pluck('name', 'id')->all(); 
		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();

		// calculate the next occurrance date based on template settings
		$nextDate = $series->nextOccurrenceDate();
		$endDate = $nextDate->copy()->addHours($series->length);

		// initialize the form object with the values from the template
		$event = new \App\Event(['name' => $series->name,
		 'slug' => $series->slug,
		 'short' => $series->short,
		 'venue_id' => $series->venue_id,
		 'series_id' => $series->id,
		 'description' => $series->description,
		 'event_type_id' => $series->event_type_id,
		 'promoter_id' => $series->promoter_id,
		 'soundcheck_at' => $series->soundcheck_at,
		 'door_at' => $series->door_at,
		 'start_at' => $nextDate,
		 'end_at' => $endDate,
		 'presale_price' => $series->presale_price,
		 'door_price' => $series->door_price,
		 'min_age' => $series->min_age,
		 'visibility_id' => $series->visibility_id
		 ]);

		return view('series.createOccurrence', compact('seriesList','event','venues','eventTypes','visibilities','tags','entities','promoters'))->with(['series' => $series]);
	}


	public function update(Series $series, SeriesRequest $request)
	{
		$msg = '';

		$series->fill($request->input())->save();

		if (!$series->ownedBy($this->user))
		{
			$this->unauthorized($request); 
		};

		$tagArray = $request->input('tag_list',[]);
		$syncArray = array();

		// check the elements in the tag list, and if any don't match, add the tag
		foreach ($tagArray as $key => $tag)
		{

            if (!Tag::find($tag))
			{
				$newTag = new Tag;
				$newTag->name = ucwords(strtolower($tag));
				$newTag->tag_type_id = 1;
				$newTag->save();

				$syncArray[strtolower($tag)] = $newTag->id;

				$msg .= ' Added tag '.$tag.'.';
			} else {
				$syncArray[$key] = $tag;
			};
		}

		$series->tags()->sync($syncArray);
		$series->entities()->sync($request->input('entity_list',[]));

		flash('Success', 'Your event template has been updated');

		return redirect('series');
	}

	public function destroy(Series $series)
	{
		$series->delete();

		return redirect('series');
	}


	/**
	 * Add a photo to a series
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function addPhoto($id, Request $request)
	{
		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

        // attach to series
        $series = Series::find($id);

        // make the photo object from the file in the request
		$photo = $this->makePhoto($request->file('file'));

        // count existing photos, and if zero, make this primary
        if (count($series->photos) == 0)
        {
            $photo->is_primary=1;
        };

		$photo->save();

        // attach to series
		$series->addPhoto($photo);
	}
	
	/**
	 * Delete a photo
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function deletePhoto($id, Request $request)
	{

		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

		// detach from event
		$series = Series::find($id);
		$series->removePhoto($photo);

		$photo = $this->deletePhoto($request->file('file'));
		$photo->save();


	}

	protected function makePhoto(UploadedFile $file)
	{
		return Photo::named($file->getClientOriginalName())
			->move($file);
	}

	/**
	 * Mark user as following the series
	 *
	 * @return Response
	 */
	public function follow($id, Request $request)
	{
		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$series = Series::find($id))
		{
			flash()->error('Error',  'No such series');
			return back();
		};

		// add the following response
		$follow = new Follow;
		$follow->object_id = $id;
		$follow->user_id = $this->user->id;
		$follow->object_type = 'series'; // 
		$follow->save();

     	Log::info('User '.$id.' is following '.$series->name);

		flash()->success('Success',  'You are now following the series - '.$series->name);

		return back();

	}

	/**
	 * Mark user as unfollowing the series
	 *
	 * @return Response
	 */
	public function unfollow($id, Request $request)
	{

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$series = Series::find($id))
		{
			flash()->error('Error',  'No such series');
			return back();
		};

		// delete the follow
		$response = Follow::where('object_id','=', $id)->where('user_id','=',$this->user->id)->where('object_type','=','series')->first();
		$response->delete();

		flash()->success('Success',  'You are no longer following the series.');

		return back();

	}

	protected function unauthorized(SeriesRequest $request)
	{
		if($request->ajax())
		{
			return response(['message' => 'No way.'], 403);
		}

		\Session::flash('flash_message', 'Not authorized');

		return redirect('/');
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
        return $request->session()
            ->put($this->prefix . $attribute, $value);
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
