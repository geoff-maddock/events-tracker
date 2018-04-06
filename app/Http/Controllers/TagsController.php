<?php namespace App\Http\Controllers;

use App\Activity;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Mail;
use App\Event;
use App\Entity;
use App\EventType;
use App\Series;
use App\EntityType;
use App\Role;
use App\Tag;
use App\Visibility;
use App\Photo;
use App\EventResponse;
use App\ResponseType;
use App\User;
use App\Follow;


class TagsController extends Controller {


	public function __construct(Tag $tag)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->tag = $tag;

        // prefix for session storage
        $this->prefix = 'app.threads.';

        // default list variables
        $this->rpp = 25;
        $this->page = 1;
        $this->sort = array('name', 'desc');
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = NULL;
        $this->hasFilter = 0;

		parent::__construct();
	}

    /**
     * Remove the specified resource from storage.
     *
     * @param Tag $tag
     * @return Response
     * @internal param int $id
     * @throws \Exception
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect('tags');
    }

	/**
 	 * Display a listing of the resource.
 	 *
 	 * @return Response
 	 */
	public function index()
	{
        $tag = NULL;

 		// get all series linked to the tag
		$series = Series::where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
                    ->with('tags','entities','occurrenceType')
					->paginate();

 		// get all the events linked to the tag
		$events = Event::orderBy('start_at', 'DESC')
					->orderBy('name', 'ASC')
                    ->with('visibility', 'tags','entities','venue','eventType','threads')
					->simplePaginate($this->rpp);

		$events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		// get all entities linked to the tag
		$entities = Entity::where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC')
                     ->with('tags','locations','roles')
					->simplePaginate($this->rpp);


		// get a list of all tags
		$tags = Tag::orderBy('name', 'ASC')->get();

		// get a list of all the user's followed tags
        if (isset($this->user)) {
            $userTags = $this->user->getTagsFollowing();
        };

		return view('tags.index', compact('series','entities','events', 'tag', 'tags','userTags'));
	}

    /**
     * Show the application dataAjax.
     *
     * @return \Illuminate\Http\Response
     */
    public function dataAjax(Request $request)
    {
    	$data = [];

        if($request->has('q')){
            $search = $request->q;
            $data = DB::table("tags")
            		->select("id","name")
            		->where('name','LIKE',"%$search%")
            		->get();
        }

        return response()->json($data);
    }

	/**
	 * Display a listing of events by tag
	 *
	 * @return Response
	 */
	public function indexTags($tag)
	{
 		$tag = urldecode($tag);

 		// get all series linked to the tag
		$series = Series::getByTag(ucfirst($tag))
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

 		// get all the events linked to the tag
		$events = Event::getByTag(ucfirst($tag))
					->orderBy('start_at', 'DESC')
					->orderBy('name', 'ASC')
					->simplePaginate($this->rpp);

		$events->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

		// get all entities linked to the tag
		$entities = Entity::getByTag(ucfirst($tag))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC')
					->simplePaginate($this->rpp);


		$tags = Tag::orderBy('name', 'ASC')->get();

		return view('tags.index', compact('series','entities','events', 'tag', 'tags'));
	}



    /**
     * Display a listing of events by tag
     *
     * @return Response
     */
    public function show($tag)
    {
        $tag = urldecode($tag);

        // get all series linked to the tag
        $series = Series::getByTag(ucfirst($tag))
            ->where(function($query)
            {
                $query->visible($this->user);
            })
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate();

        // get all the events linked to the tag
        $events = Event::getByTag(ucfirst($tag))
            ->orderBy('start_at', 'DESC')
            ->orderBy('name', 'ASC')
            ->simplePaginate($this->rpp);

        $events->filter(function($e)
        {
            return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        // get all entities linked to the tag
        $entities = Entity::getByTag(ucfirst($tag))
            ->where(function($query)
            {
                $query->active()
                    ->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
            })
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->simplePaginate($this->rpp);


        $tags = Tag::orderBy('name', 'ASC')->get();

        return view('tags.index', compact('series','entities','events', 'tag', 'tags'));
    }



    /**
	 * Show a form to create a new Article.
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
		$seriesList = [''=>''] + Series::orderBy('name','ASC')->pluck('name', 'id')->all(); 
		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();

		return view('events.create', compact('venues','eventTypes','visibilities','tags','entities','promoters','seriesList'));
	}


	/**
	 * Add a photo to an event
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function addPhoto($id, Request $request)
	{

		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

		$photo = $this->makePhoto($request->file('file'));
		$photo->save();

		// attach to event
		$event = Event::find($id);
		$event->addPhoto($photo);
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
		$event = Event::find($id);
		$event->removePhoto($photo);

		$photo = $this->deletePhoto($request->file('file'));
		$photo->save();


	}

	protected function makePhoto(UploadedFile $file)
	{
		return Photo::named($file->getClientOriginalName())
			->move($file);
	}

	/**
	 * Mark user as following the tag
	 *
	 * @return Response
	 */
	public function follow($id, Request $request)
	{
		$type = 'tag';

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		// how can i derive this class from a string?
		if (!$object = call_user_func("App\\".ucfirst($type)."::find", $id)) // Tag::find($id)) 
		{
			flash()->error('Error',  'No such '.$type);
			return back();
		};

		$tag = $object;

		// add the following response
		$follow = new Follow;
		$follow->object_id = $id;
		$follow->user_id = $this->user->id;
		$follow->object_type = $type; // 
		$follow->save();

     	Log::info('User '.$id.' is following '.$object->name);

        // add to activity log
        Activity::log($tag, $this->user, 6);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the tag - ' . $object->name,
                'Success' => view('tags.link')
                    ->with(compact('tag'))
                    ->render()
            ];
        };

		flash()->success('Success',  'You are now following the '.$type.' - '.$object->name);

		return back();

	}

    /**
     * Mark user as unfollowing the tag.
     *
     * @param $id
     * @param Request $request
     * @return Response
     * @throws \Throwable
     */
	public function unfollow($id, Request $request)
	{
		$type = 'tag';

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$tag = Tag::find($id))
		{
			flash()->error('Error',  'No such '.$type);
			return back();
		};

		// add to activity log
        Activity::log($tag, $this->user, 7);

		// delete the follow
		$response = Follow::where('object_id','=', $id)->where('user_id','=',$this->user->id)->where('object_type','=',$type)->first();
		$response->delete();

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the tag - ' . $tag->name,
                'Success' => view('tags.link')
                    ->with(compact('tag'))
                    ->render()
            ];
        };

		flash()->success('Success',  'You are no longer following the ' . $type . ' ' . $tag->name);

		return back();
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
     * @return array
     */
    public function getDefaultSort()
    {
        return array('id', 'desc');
    }


    /**
     * Get the default filters array
     *
     * @return array
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

    /**
     * Builds the criteria from the session
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function buildCriteria (Request $request)
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = Tag::query();

        // add the criteria from the session
        // check request for passed filter values
        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%' . $name . '%');
            $filters['filter_name'] = $name;
        }


        // change this - should be separate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }
}
