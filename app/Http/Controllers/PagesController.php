<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Event;
use App\Entity;
use App\Series;
use App\Activity;

class PagesController extends Controller {

	public function __construct(Event $event)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->event = $event;

		$this->rpp = 5;
		parent::__construct();
	}

	public function index()
	{

		$future_events = Event::where('start_at','>=',Carbon::now())
						->orderBy('start_at', 'asc')
						->get();

		$past_events = Event::where('start_at','<',Carbon::now())
						->orderBy('start_at', 'desc')
						->get();


		return view('events.index', compact('future_events', 'past_events'));

	}

	public function search(Request $request)
	{
		$slug = $request->input('keyword');

		// override rpp, while not breaking template that tries to render 
		$this->rpp = 1000;

		$events = Event::getByEntity(strtolower($slug))
					->orWhereHas('tags', function($q) use ($slug)
						{
							$q->where('name','=', ucfirst($slug));
						})
					->orWhere('name','like','%'.$slug.'%')
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'DESC')
					->orderBy('name', 'ASC')
					->simplePaginate($this->rpp);

		$series = Series::getByEntity(strtolower($slug))
					->orWhereHas('tags', function($q) use ($slug)
						{
							$q->where('name','=', ucfirst($slug));
						})
					->orWhere('name','like','%'.$slug.'%')
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'DESC')
					->orderBy('name', 'ASC')
					->simplePaginate($this->rpp);


		$entities = Entity::where('name','like','%'.$slug.'%')
		->orWhereHas('tags', function($q) use ($slug)
						{
							$q->where('name','=', ucfirst($slug));
						})
		->orderBy('entity_type_id', 'ASC')
		->orderBy('name', 'ASC')
		->simplePaginate($this->rpp);

		return view('events.search', compact('events', 'entities', 'series', 'slug'));

	}

	public function help()
	{
		return view('pages.help');
	}

	public function about()
	{
		return view('pages.about');
	}

	public function settings()
	{
		return view('pages.settings');
	}

	public function home()
	{

		$events = Event::getByStartAt(Carbon::today())
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		return view('pages.home', compact('events'));
	}

	public function activity()
	{

		$activities = Activity::orderBy('created_at', 'DESC')
					->paginate();

		return view('pages.activity', compact('activities'));
	}

}
