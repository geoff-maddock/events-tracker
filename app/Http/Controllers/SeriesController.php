<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\SeriesRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use App\Series;
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
		
		$this->rpp = 5;

		parent::__construct();
	}


	public function index()
	{
		$series = $this->series
		->where('cancelled_at', NULL)
		->orderBy('occurrence_type_id','ASC')
		->orderBy('occurrence_week_id', 'ASC')
		->orderBy('occurrence_day_id', 'ASC')
		->orderBy('name', 'ASC')
		->get();

		$series = $series->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});


		return view('series.index', compact('series'));
	}

	/**
	 * Display a listing of event series in a week view
	 *
	 * @return Response
	 */
	public function indexWeek()
	{
		$this->rpp = 5;

		// this is more complex because we want to show weeklies that fall on the days, plus monthlies that fall on the days
		// may be an iterative process that is called from the template to the series model that checks against each criteria and builds a list that way
		$series = Series::future()->get();

		return view('series.indexWeek', compact('events'));
	}


	/**
	 * Display a listing of series related to entity
	 *
	 * @return Response
	 */
	public function indexRelatedTo($slug)
	{
 		$slug = urldecode($slug);

		$series = Series::getByEntity(strtolower($slug))
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		return view('series.index', compact('series','slug'));
	}

	/**
	 * Display a listing of events by tag
	 *
	 * @return Response
	 */
	public function indexTags($tag)
	{
 
  		$tag = urldecode($tag);

		$series = Series::getByTag(ucfirst($tag))
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();
					

		return view('series.index', compact('series', 'tag'));
	}

	/**
	 * Show a form to create a new series.
	 *
	 * @return view
	 **/

	public function create()
	{

		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->lists('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->lists('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->lists('name', 'id')->all();

		$occurrenceTypes = [''=>''] + OccurrenceType::lists('name', 'id')->all();
		$days = [''=>''] + OccurrenceDay::lists('name', 'id')->all();
		$weeks = [''=>''] + OccurrenceWeek::lists('name', 'id')->all();

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->lists('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->lists('name','id')->all();

		return view('series.create', compact('venues','eventTypes','visibilities','tags','entities','promoters', 'weeks','days', 'occurrenceTypes'));
	}

	public function show(Series $series)
	{

		$events = $series->events()->paginate($this->rpp);

		return view('series.show', compact('series','events'));
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
		$venues = [''=>''] + Entity::getVenues()->lists('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->lists('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->lists('name', 'id')->all();

		$occurrenceTypes = [''=>''] + OccurrenceType::lists('name', 'id')->all();
		$days = [''=>''] + OccurrenceDay::lists('name', 'id')->all();
		$weeks = [''=>''] + OccurrenceWeek::lists('name', 'id')->all();

		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->lists('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->lists('name','id')->all();


		return view('series.edit', compact('series','venues','eventTypes','visibilities','tags','entities','promoters', 'weeks','days', 'occurrenceTypes'));
	}



	public function createOccurrence(Request $request)
	{
		// create an event occurence based on the event template

		$series = Series::find($request->id);

		// get a list of venues
		$venues = [''=>''] + Entity::getVenues()->lists('name','id')->all();

		// get a list of promoters
		$promoters = [''=>''] + Entity::whereHas('roles', function($q)
		{
			$q->where('name','=','Promoter');
		})->orderBy('name','ASC')->lists('name','id')->all();

		$eventTypes = [''=>''] + EventType::orderBy('name','ASC')->lists('name', 'id')->all();

		$seriesList = [''=>''] + Series::orderBy('name','ASC')->lists('name', 'id')->all(); 
		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->lists('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->lists('name','id')->all();

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

		$photo = $this->makePhoto($request->file('file'));
		$photo->save();

		// attach to series
		$series = Series::find($id);
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

}
