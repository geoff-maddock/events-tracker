<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\ThreadRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Mail;
use App\Thread;
use App\Event;
use App\Entity;
use App\ThreadCategory;
use App\Series;
use App\Tag;
use App\Visibility;
use App\Activity;


class ThreadsController extends Controller
{


	public function __construct(Thread $thread)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->thread = $thread;

		$this->rpp = 15;
		parent::__construct();
	}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
		$threads = Thread::orderBy('created_at', 'desc')->paginate($this->rpp);
		$threads->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

        return view('threads.index', compact('threads'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexAll()
    {
        $threads = Thread::orderBy('created_at', 'desc')->paginate(1000000);
		$threads->filter(function($e)
		{
			return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
		});

        return view('threads.index', compact('threads'));
    }

	/**
	 * Display a listing of threads by category
	 *
	 * @return Response
	 */
	public function indexCategories($slug)
	{
		$threads = Thread::getByCategory(strtolower($slug))
					->where(function($query)
					{
						$query->visible($this->user);
					})
					->orderBy('created_at', 'ASC')
					->paginate($this->rpp);

        return view('threads.index', compact('threads', 'slug'));
	}


	/**
	 * Display a listing of threads by tag
	 *
	 * @return Response
	 */
	public function indexTags($tag)
	{
 		$tag = urldecode($tag);

		$threads = Thread::getByTag(ucfirst($tag))
					->orderBy('created_at', 'ASC')
					->paginate($this->rpp);

		return view('threads.index', compact('threads', 'tag'));
	}

	/**
	 * Display a listing of threads by series
	 *
	 * @return Response
	 */
	public function indexSeries($tag)
	{
 		$tag = urldecode($tag);

		$threads = Thread::getBySeries(ucfirst($tag))
					->orderBy('created_at', 'ASC')
					->paginate($this->rpp);

		return view('threads.index', compact('threads', 'tag'));
	}

	/**
	 * Display a listing of threads by entity
	 *
	 * @return Response
	 */
	public function indexRelatedTo($slug)
	{
 		$tag = urldecode($slug);

		$threads = Thread::getByEntity(ucfirst($tag))
					->orderBy('created_at', 'ASC')
					->paginate($this->rpp);

		return view('threads.index', compact('threads', 'tag'));
	}


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		$threadCategories = [''=>''] + ThreadCategory::orderBy('name','ASC')->lists('name', 'id')->all(); 
		$visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->lists('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->lists('name','id')->all();
		$series = Series::orderBy('name','ASC')->lists('name','id')->all();


		return view('threads.create', compact('threadCategories','visibilities','tags','entities','series'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
	public function store(ThreadRequest $request, Thread $thread)
	{
		$msg = '';

		// get the request
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

		$thread = $thread->create($input);

		$thread->tags()->attach($syncArray);
		$thread->entities()->attach($request->input('entity_list'));
		$thread->series()->attach($request->input('series_list'));

		// here, make a call to notify all users who are following any of the sync'd tags
		//$this->notifyFollowing($thread);

		// add to activity log
		Activity::log($thread, $this->user, 1);

		flash()->success('Success', 'Your thread has been created');

		return redirect()->route('threads.index');
	}

	/**
	 * Create a conversation slug.
	 *
	 * @param  string $title
	 * @return string
	 */
	public function makeSlugFromTitle($title)
	{
	    $slug = Str::slug($title);

	    $count = Thread::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

	    return $count ? "{$slug}-{$count}" : $slug;
	}

	protected function unauthorized(ThreadRequest $request)
	{
		if($request->ajax())
		{
			return response(['message' => 'No way.'], 403);
		}

		\Session::flash('flash_message', 'Not authorized');

		return redirect('/');
	}


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Thread $thread)
    {
    	// call a log for this and prevent it from going out of control
    	$thread->views++;
    	$thread->save();

		return view('threads.show', compact('thread'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Thread $thread)
    {
		$this->middleware('auth');

		$threadCategories = [''=>''] + ThreadCategory::orderBy('name','ASC')->lists('name', 'id')->all();
		$visibilities = [''=>''] + Visibility::lists('name', 'id')->all();
		$tags = Tag::orderBy('name','ASC')->lists('name','id')->all();
		$entities = Entity::orderBy('name','ASC')->lists('name','id')->all();
		$series = Series::orderBy('name','ASC')->lists('name','id')->all();

		return view('threads.edit', compact('thread', 'threadCategories', 'visibilities','tags','entities','series'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Thread $thread, ThreadRequest $request)
    {
		$msg = '';

		$thread->fill($request->input())->save();

		if (!$thread->ownedBy($this->user))
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

		$thread->tags()->sync($syncArray);
		$thread->entities()->sync($request->input('entity_list',[]));
		$thread->series()->sync($request->input('series_list',[]));

		// add to activity log
		Activity::log($thread, $this->user, 2);

		flash('Success', 'Your thread has been updated');

		return redirect('threads');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Thread $thread)
    {
		// add to activity log
		Activity::log($thread, $this->user, 3);

		$thread->delete();

		flash()->success('Success', 'Your thread has been deleted!');

		return redirect('threads');
    }
}
