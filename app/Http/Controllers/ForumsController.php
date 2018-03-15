<?php namespace App\Http\Controllers;

use Gate;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForumRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Mail;
use App\Forum;
use App\Thread;
use App\Event;
use App\Entity;
use App\Series;
use App\Tag;
use App\Visibility;
use App\Activity;

class ForumsController extends Controller
{

    // define a list of variables
    protected $rpp;
    protected $page;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $hasFilter;

    public function __construct(Forum $forum)
    {
        $this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
        $this->forum = $forum;

        // prefix for session storage
        $this->prefix = 'app.forums.';

        // default list variables
        $this->rpp = 10;
        $this->page = 1;
        $this->sort = array('name', 'desc');
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = NULL;
        $this->hasFilter = 1;
        parent::__construct();
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }


        $forums = Forum::orderBy('created_at', 'desc')->paginate($this->rpp);
        $forums->filter(function($e)
        {
            return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        return view('forums.index', compact('forums'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();


        return view('forums.create', compact('visibilities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ForumRequest $request
     * @param Forum $forum
     * @return \Illuminate\Http\Response
     */
    public function store(ForumRequest $request, Forum $forum)
    {
        $msg = '';

        // get the request
        $input = $request->all();

        $forum = $forum->create($input);

        // add to activity log
        Activity::log($forum, $this->user, 1);

        flash()->success('Success', 'Your forum has been created');

        return redirect()->route('forums.index');
    }

    /**
     * Update the page list parameters from the request
     * @param $request
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
     * Display the specified resource.
     *
     * @param Forum $forum
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Forum $forum, Request $request)
    {
         // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);


        $threads = Thread::where('forum_id', $forum->id)->orderBy('created_at', 'desc')->paginate(1000000);
        $threads->filter(function($e)
        {
            return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        // pass a slug for the forum
        $slug = $forum->description;

        return view('threads.index')
            ->with(compact('threads','slug'))
        ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder,
        'hasFilter' => $this->hasFilter,
        'filters' => $filters,
        'filter_name' => isset($filters['filter_name']) ? $filters['filter_name'] : NULL,  // there should be a better way to do this...
        'filter_user' => isset($filters['filter_user']) ? $filters['filter_user'] : NULL,
        'filter_tag' => isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL
        ]);
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
     * Get the default filters array
     *
     * @return array
     */
    public function getDefaultFilters()
    {
        return array();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Forum $forum)
    {
        $this->middleware('auth');

        $visibilities = [''=>''] + Visibility::pluck('name', 'id')->all();

        return view('forums.edit', compact('forum', 'visibilities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ForumRequest $request, Forum $forum)
    {
        $msg = '';

        $forum->fill($request->input())->save();

        if (!$forum->ownedBy($this->user))
        {
            $this->unauthorized($request); 
        };

              // add to activity log
        Activity::log($forum, $this->user, 2);

        flash('Success', 'Your forum has been updated');

        return redirect('forums');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Forum $forum)
    {
        // add to activity log
        Activity::log($forum, $this->user, 3);

        $forum->delete();

        flash()->success('Success', 'Your forum has been deleted!');

        return redirect('forums');
    }
}
