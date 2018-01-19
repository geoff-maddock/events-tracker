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
use App\Tag;
use App\User;
use App\Thread;

class PagesController extends Controller {

	public function __construct(Event $event)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update','activity','tools')]);
		$this->event = $event;

		// default list variables
		$this->rpp = 5;
		$this->dayOffset = 0;

		parent::__construct();
	}

	/**
	 * Update the page list parameters from the request
	 *
	 */
	protected function updatePaging($request)
	{
 		// set starting day offset
 		if ($request->input('day_offset')) {
 			$this->dayOffset = $request->input('day_offset');
 		};

 		// set results per page
 		if ($request->input('rpp')) {
 			$this->rpp = $request->input('rpp');
 		};
	}

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
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

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function search(Request $request)
	{
		$slug = $request->input('keyword');

		// override rpp, while not breaking template that tries to render 
		$this->rpp = 20;

		$events = Event::getByEntity(strtolower($slug))
					->orWhereHas('tags', function($q) use ($slug)
						{
							$q->where('name','=', ucfirst($slug));
						})
					->orWhereHas('series', function($q) use ($slug)
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
					->paginate($this->rpp);

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
					->paginate($this->rpp);


		$entities = Entity::where('name','like','%'.$slug.'%')
				->orWhereHas('tags', function($q) use ($slug)
								{
									$q->where('name','=', ucfirst($slug));
								})
				->orWherehas('aliases', function($q) use ($slug)
								{
									$q->where('name','=', ucfirst($slug));
								})
				->orderBy('entity_type_id', 'ASC')
				->orderBy('name', 'ASC')
				->paginate($this->rpp);

		$tags = Tag::where('name','like','%'.$slug.'%')
				->orderBy('name', 'ASC')
				->simplePaginate($this->rpp);

		$users = User::where('name','like','%'.$slug.'%')
				->orderBy('name', 'ASC')
				->simplePaginate($this->rpp);

        $threads = Thread::where('name','like','%'.$slug.'%')
            ->orWhereHas('tags', function($q) use ($slug)
            {
                $q->where('name','=', ucfirst($slug));
            })
            ->orderBy('name', 'ASC')
            ->paginate($this->rpp);

		return view('events.search', compact('events', 'entities', 'series','users','threads','tags','slug'));

	}

	public function help()
	{
		return view('pages.help');
	}

	public function about()
	{
		return view('pages.about');
	}

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function tos()
    {
        return view('pages.tos');
    }

    public function settings()
	{
		return view('pages.settings');
	}

	public function home(Request $request)
	{
 		// updates sort, rpp from request
 		$this->updatePaging($request);

		$events = Event::getByStartAt(Carbon::today())
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('start_at', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

		// handle the request if ajax
		if ($request->ajax()) {
            return view('pages.4days')
		        	->with(['rpp' => $this->rpp, 'dayOffset' => $this->dayOffset])
        			->with(compact('events'))
        			->render();
		}

		return view('pages.home')
		        	->with(['rpp' => $this->rpp, 'dayOffset' => $this->dayOffset])
        			->with(compact('events'));
	}

	public function activity(Request $request)
	{
        $this->middleware('auth');
        $offset = 0;
        if ($request->input('offset')) {
            $offset = $request->input('offset');
        };

		$activities = Activity::orderBy('created_at', 'DESC')
                    ->take(100)
                    ->offset($offset)
                    ->get()
                    ->groupBy(function($activity) {
                        return $activity->created_at->format('Y-m-d');
                    });
            //->paginate();

//		return $activities;
		return view('pages.activity', compact('activities'));
	}

    public function tools(Request $request)
    {

        $this->middleware('auth');

        $user = $request->user();
        if (!$user->can('show_admin')) {
            die('cannot show admin)');
        }

        // get all the events with no photo
        $events = Event::has('photos', '<', 1)
            ->where('primary_link','<>','')
            ->where('primary_link','like','%facebook%')
            ->get();


        return view('pages.tools',compact('events'));
    }


}
