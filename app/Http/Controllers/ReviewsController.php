<?php namespace App\Http\Controllers;


use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\OccurrenceDay;
use App\OccurrenceType;
use App\OccurrenceWeek;
use App\ReviewType;
use App\Thread;
use App\Traits\Followable;
use Illuminate\View\View;
use PhpParser\Node\Expr\Array_;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use SammyK;

use DB;
use Log;
use Mail;
use App\EventReview;
use App\Event;
use App\Entity;
use App\EventType;
use App\Tag;
use App\Visibility;
use App\Photo;
use App\EventResponse;
use App\User;
use App\Activity;
use App\Services\RssFeed;


class ReviewsController extends Controller
{
    protected $prefix;
    protected $rpp;
    protected $page;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $hasFilter;

    public function __construct(Event $event)
    {
        $this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
        $this->event = $event;

        // prefix for session storage
        $this->prefix = 'app.reviews.';

        // default list variables
        $this->rpp = 8;
        $this->gridRpp = 24;
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
        $filters = array();
        if (!empty($request->input('filter_sort_by'))) {
            $this->sortBy = $request->input('filter_sort_by');
            $filters['filter_sort_by'] = $this->sortBy;
        };

        if (!empty($request->input('filter_sort_order'))) {
            $this->sortOrder = $request->input('filter_sort_order');
            $filters['filter_sort_order'] = $this->sortOrder;
        };

        if (!empty($request->input('filter_rpp'))) {
            $this->rpp = $request->input('filter_rpp');
            $filters['filter_rpp'] = $this->rpp;
        };

        // save filters to session
        $this->setFilters($request, $filters);
    }

    /**
     * Update the filters parameters from the request
     *
     */
    protected function updateFilters($request)
    {
        $filters = array();

        if (!empty($request->input('filter_name'))) {
            $filters['filter_name'] = $request->input('filter_name');
        };

        if (!empty($request->input('filter_venue'))) {
            $filters['filter_venue'] = $request->input('filter_venue');
        };

        if (!empty($request->input('filter_tag'))) {
            $filters['filter_tag'] = $request->input('filter_tag');
        };

        if (!empty($request->input('filter_related'))) {
            $filters['filter_related'] = $request->input('filter_related');
        };

        // save filters to session
        $this->setFilters($request, $filters);
    }

    /**
     * Builds the criteria from the session
     *
     * @return $query
     */
    public function buildCriteria(Request $request)
    {
        $hasFilter = 1;

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = EventReview::query();

        // add the criteria from the session
        // check request for passed filter values
        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%' . $name . '%');
            $filters['filter_name'] = $name;
        }

        if (!empty($filters['filter_tag'])) {
            $tag = $filters['filter_tag'];
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });

            // add to filters array
            $filters['filter_tag'] = $tag;
        }

        if (!empty($filters['filter_venue'])) {
            $venue = $filters['filter_venue'];
            // add has clause
            $query->whereHas('venue', function ($q) use ($venue) {
                $q->where('name', '=', $venue);
            });

            // add to filters array
            $filters['filter_venue'] = $venue;
        };

        if (!empty($filters['filter_related'])) {
            $related = $filters['filter_related'];
            $query->whereHas('entities', function ($q) use ($related) {
                $q->where('name', '=', ucfirst($related));
            });

            // add to filters array
            $filters['filter_related'] = $related;
        }

        // change this - should be separate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

    /**
     * Reset filter action.
     *
     * @param Request $request
     */
    public function executeReset(Request $request)
    {
        if ($request->input('criteria')) {
            $this->setCriteria($request->input('criteria'));
        }
        $this->setFilters($this->getDefaultFilters(), NULL);
        $request->session()->put('defaultFilter', 0);
        $this->setPage(1);
        $this->executeFilterRedirect();
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
        return $this->getAttribute('rpp', $this->rpp, $request);
    }

    /**
     * Get the sort order and column
     *
     * @return array
     */
    public function getSort(Request $request)
    {
        return $this->getAttribute('sort', $this->getDefaultSort(), $request);
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
        return $request->session()->put($this->prefix . $attribute, $value);
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
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return View
     * @throws \Throwable
     */
    public function index(Request $request)
    {
        $hasFilter = 1;

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        // base criteria
        $query = $this->buildCriteria($request);//,'start_at', 'desc' );


        // get reviews
        $reviews = $query->paginate($this->rpp);


        return view('reviews.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter,  'filters' => $filters,
                'filter_name' => isset($filters['filter_name']) ? $filters['filter_name'] : NULL,  // there should be a better way to do this...
                'filter_venue' => isset($filters['filter_venue']) ? $filters['filter_venue'] : NULL,
                'filter_tag' => isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL,
                'filter_related' => isset($filters['filter_related']) ? $filters['filter_related'] : NULL,
                'filter_rpp' => isset($filters['filter_rpp']) ? $filters['filter_rpp'] : NULL
            ])
            ->with(compact('reviews'))
            ->render();
    }



    /**
     * Filter the list of events
     *
     * @param Request $request
     * @return View
     * @internal param $Request
     * @throws \Throwable
     */
    public function filter(Request $request, EventFilters $filters)
    {
        $hasFilter = 1;

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // updates sort, rpp from request
        $this->updatePaging($request);

        // base criteria
        $query = $this->review->orderBy($this->sortBy, $this->sortOrder);


        // add the criteria from the session

        // check request for passed filter values

        if (!empty($request->input('filter_name'))) {
            // getting name from the request
            $name = $request->input('filter_name');
            $query->where('name', 'like', '%' . $name . '%');

            // add to filters array
            $filters['filter_name'] = $name;
        }

        if (!empty($request->input('filter_venue'))) {
            $venue = $request->input('filter_venue');
            // add has clause
            $query->whereHas('venue', function ($q) use ($venue) {
                $q->where('name', '=', $venue);
            });


            // add to filters array
            $filters['filter_venue'] = $venue;
        };

        if (!empty($request->input('filter_tag'))) {
            $tag = $request->input('filter_tag');
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });

            // add to filters array
            $filters['filter_tag'] = $tag;
        }

        if (!empty($request->input('filter_related'))) {
            $related = $request->input('filter_related');
            $query->whereHas('entities', function ($q) use ($related) {
                $q->where('name', '=', ucfirst($related));
            });

            // add to filters array
            $filters['filter_related'] = $related;
        }

        // change this - should be seperate
        if (!empty($request->input('filter_rpp'))) {
            $this->rpp = $request->input('filter_rpp');
            $filters['filter_rpp'] = $this->rpp;
        }

        // save filters to session
        $this->setFilters($request, $filters);

        // get future events
        $reviews = $query->paginate($this->rpp);


        return view('reviews.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter,  'filters' => $filters,
                'filter_name' => isset($filters['filter_name']) ? $filters['filter_name'] : NULL,  // there should be a better way to do this...
                'filter_venue' => isset($filters['filter_venue']) ? $filters['filter_venue'] : NULL,
                'filter_tag' => isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL,
                'filter_related' => isset($filters['filter_related']) ? $filters['filter_related'] : NULL,
                'filter_rpp' => isset($filters['filter_rpp']) ? $filters['filter_rpp'] : NULL
            ])
            ->with(compact('reviews'))
            ->render();
    }


    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function indexAll(Request $request)
    {
        $this->hasFilter = 1;

        // updates sort, rpp from request
        $this->updatePaging($request);

        $future_events = EventReview::future()->paginate(100000);
        $future_events->filter(function ($e) {
            return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        $past_events = EventReview::past()->paginate(100000);
        $past_events->filter(function ($e) {
            return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        return view('reviews.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'));
    }



    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function indexPast(Request $request)
    {
        $this->hasFilter = 1;

        // updates sort, rpp from request
        $this->updatePaging($request);

        $this->rpp = 10;

        $past_events = EventReview::past()->paginate($this->rpp);
        $past_events->filter(function ($e) {
            return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        return view('reviews.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('past_events'));
    }




    /**
     * Reset the filtering of reviews
     *
     * @return Response
     * @throws \Throwable
     */
    public function reset(Request $request)
    {
        // doesn't have filter, but temp
        $hasFilter = 1;

        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        // base criteria
        $query = $this->review->get();

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get future events
        $reviews = $query->paginate($this->rpp);


        if ($redirect = $request->input('redirect'))
        {
            return redirect()->route($redirect);
        };

        return view('reviews.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('reviews'))

            ->render();

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

		$reviewTypes = [''=>''] + ReviewType::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$events = Event::orderBy('name','ASC')->pluck('name','id')->all();

		return view('reviews.create', compact('reviewTypes','visibilities','tags','events'));
	}





    /**
     * Makes a call to the FB API if there is a link present and downloads the event cover photo

     */
    public function getToken()
    {
        $fb = app(SammyK\LaravelFacebookSdk\LaravelFacebookSdk::class);

        // Obtain an access token.
        try {
            $token = $fb->getAccessTokenFromRedirect();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }

        // Access token will be null if the user denied the request
        // or if someone just hit this URL outside of the OAuth flow.
        if (! $token) {
            // Get the redirect helper
            $helper = $fb->getRedirectLoginHelper();

            if (! $helper->getError()) {
                abort(403, 'Unauthorized action.');
            }

            // User denied the request
            dd(
                $helper->getError(),
                $helper->getErrorCode(),
                $helper->getErrorReason(),
                $helper->getErrorDescription()
            );
        }

        if (! $token->isLongLived()) {
            // OAuth 2.0 client handler
            $oauth_client = $fb->getOAuth2Client();

            // Extend the access token.
            try {
                $token = $oauth_client->getLongLivedAccessToken($token);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                dd($e->getMessage());
            }
        }

        $fb->setDefaultAccessToken($token);
    }


    public function show(Event $event)
	{
        if (empty((array) $event))
        {
            abort(404);
        };

        $thread = Thread::where('event_id','=',$event->id)->get();

        return view('reviews.show', compact('event'))->with(['thread' => $thread ? $thread->first() : NULL]);
	}


	public function store(EventRequest $request, Event $event)
	{
		$msg = '';

		// get the request
		$input = $request->all();

		// validate - hmm, isn't this doing it elsewhere?

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

				$syncArray[] = $newTag->id;

				$msg .= ' Added tag '.$tag.'.';
			} else {
				$syncArray[$key] = $tag;
			};
		}

		$event = $event->create($input);

		$event->tags()->attach($syncArray);
		$event->entities()->attach($request->input('entity_list'));

		// here, make a call to notify all users who are following any of the sync'd tags
		$this->notifyFollowing($event);

		// add to activity log
		Activity::log($event, $this->user, 1);

		flash()->success('Success', 'Your event has been created');

		return redirect()->route('reviews.index');
	}

    /**
     * @param $event
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($event)
	{
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

		// notify users following any of the tags
		$tags = $event->tags()->get();
		$users = array();

		// improve this so it will only sent one email to each user per event, and include a list of all tags they were following that led to the notification
		foreach ($tags as $tag)
		{
			foreach ($tag->followers() as $user)
			{
				// if the user hasn't already been notified, then email them
				if (!array_key_exists($user->id, $users))
				{
					Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $tag, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

						$m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
					});
					$users[$user->id] = $tag->name;
				};
			};
		};

		// notify users following any of the entities
		$entities = $event->entities()->get();

		// improve this so it will only sent one email to each user per event, and include a list of entities they were following that led to the notification
		foreach ($entities as $entity)
		{
			foreach ($entity->followers() as $user)
			{

				// if the user hasn't already been notified, then email them
				if (!array_key_exists($user->id, $users))
				{
                    Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $entity, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $entity, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

						$m->to($user->email, $user->name)->subject($site.': '.$entity->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
					});
					$users[$user->id] = $entity->name;
				};
			};
		};

		return back();
	}

	protected function unauthorized(EventRequest $request)
	{
		if($request->ajax())
		{
			return response(['message' => 'No way.'], 403);
		}

		\Session::flash('flash_message', 'Not authorized');

		return redirect('/');
	}

	public function edit(Event $event)
	{
		$this->middleware('auth');

        // moved necessary lists into AppServiceProvider

		return view('reviews.edit', compact('event'));
	}


	public function update(Event $event, EventRequest $request)
	{
		$msg = '';

		$event->fill($request->input())->save();

		if (!$event->ownedBy($this->user))
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

		$event->tags()->sync($syncArray);
		$event->entities()->sync($request->input('entity_list',[]));

		// add to activity log
		Activity::log($event, $this->user, 2);

		flash()->success('Success', 'Your event has been updated');

		return redirect('events');
	}

	public function destroy(Event $event)
	{
		// add to activity log
		Activity::log($event, $this->user, 3);

		$event->delete();

		flash()->success('Success', 'Your event has been deleted!');

		return redirect('events');
	}




}
