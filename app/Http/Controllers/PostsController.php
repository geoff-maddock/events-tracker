<?php

namespace App\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;


use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Carbon\Carbon;

use Gate;
use DB;
use Log;
use Mail;
use App\Post;
use App\Thread;
use App\Entity;
use App\Series;
use App\Tag;
use App\Visibility;
use App\Activity;
use App\Like;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PostsController extends Controller
{

    protected $post;
    protected $rpp;

    public function __construct(Post $post)
    {
        $this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);

        $this->post = $post;
        $this->rpp = 20;

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

        $posts = Post::orderBy('created_at', 'desc')->paginate($this->rpp);
        $posts->filter(function($e)
        {
            return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        return view('posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $visibilities = [''=>''] + Visibility::orderBy('name','ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
        $entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();
        $series = Series::orderBy('name','ASC')->pluck('name','id')->all();


        return view('posts.create', compact('visibilities','tags','entities','series'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Thread $thread
     * @return \Illuminate\Http\Response
     * @internal param Request $request
     */
    public function store(Thread $thread)
    {
        if (auth()->id() == config('app.superuser'))
        {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

        $thread->addPost([
            'body' => request('body'),
            'created_by' => auth()->id(),
            'visibility_id' => 1,
            'allow_html' => $allow_html
            ]);

        $post = Post::where('thread_id' ,'=', $thread->id)->orderBy('id','DESC')->first();

        // here, notify anybody following the thread
        $this->notifyFollowing($post);

        // add to activity log
        Activity::log($post, $this->user, 1);

        return back();
    }

    /**
     * @param Post $post
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($post)
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        $thread = $post->thread;

        // notify users following any of the tags
        $tags = $thread->tags()->get();
        $users = array();

        // notify users who are following this thread 
        foreach ($thread->followers() as $user)
        {
            // if the user hasn't already been notified, then email them
            if (!array_key_exists($user->id, $users))
            {
                Mail::send('emails.following-thread-post', ['user' => $user, 'post' => $post, 'thread' => $thread, 'object' => $thread, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $post, $thread, $reply_email, $site, $url) {
                    $m->from($reply_email, $site);

                    $m->to($user->email, $user->name)->subject($site.': New post by '.$post->user->name.' in thread '.$thread->name);
                });
                $users[$user->id] = $thread->name;
            };
        };

        // notify users following any tags related to the thread

        foreach ($tags as $tag)
        {
            foreach ($tag->followers() as $user)
            {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users))
                {
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $thread, $tag, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$thread->created_at->format('D F jS').' '.$thread->name);
                    });
                    $users[$user->id] = $tag->name;
                };
            };
        };

        // notify users following any of the series
        $seriess = $thread->series()->get();

        foreach ($seriess as $series)
        {
            foreach ($series->followers() as $user)
            {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users))
                {
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $series, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $thread, $series, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$series->name.' :: '.$thread->created_at->format('D F jS').' '.$thread->name);
                    });
                    $users[$user->id] = $series->name;
                };
            };
        };

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param Post $post
     * @return \Illuminate\Http\Response
     * @internal param int $id
     */
    public function show(Post $post)
    {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        // call a log for this and prevent it from going out of control
        $post->views++;
        $post->save();

        $route = route('threads.show',['id' =>$post->thread_id]).'#post-'.$post->id;
        return redirect($route);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Post $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        $this->middleware('auth');

        $visibilities = [''=>''] + Visibility::pluck('name', 'id')->all();
        $tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
        $entities = Entity::orderBy('name','ASC')->pluck('name','id')->all();

        return view('posts.edit', compact('post', 'visibilities', 'tags','entities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Post $post
     * @param PostRequest|Request $request
     * @return \Illuminate\Http\Response
     * @internal param int $id
     */
    public function update(Post $post, PostRequest $request)
    {
        $msg = '';

        $post->fill($request->input())->save();

        if (!$post->ownedBy($this->user))
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

        $post->tags()->sync($syncArray);
        $post->entities()->sync($request->input('entity_list',[]));

        // add to activity log
        Activity::log($post, $this->user, 2);

        flash('Success', 'Your post has been updated');

       return redirect()->route('threads.show',['id' => $post->thread_id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Post $post
     * @return \Illuminate\Http\Response
     * @throws \Exception
     * @internal param int $id
     */
    public function destroy(Post $post)
    {
        $id = $post->thread_id;
        $thread = $post->thread;

        if ($this->user->cannot('destroy', $post))
        {
            flash('Error', 'Your are not authorized to delete the post.');
            return redirect()->route('threads.show',['id' => $id]);
        };

        // add to activity log
        Activity::log($post, $this->user, 3);

        $post->delete();

        flash()->success('Success', 'Your post has been deleted!');

       return redirect()->route('threads.show',['id' => $id]);

    }

    /**
     * Mark user as liking the post
     *
     * @return Response
     */
    public function like($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user)
        {
            flash()->error('Error',  'No user is logged in.');
            return back();
        };

        if (!$post = Post::find($id))
        {
            flash()->error('Error',  'No such post');
            return back();
        };

        // add the like response
        $like = new Like;
        $like->object_id = $id;
        $like->user_id = $this->user->id;
        $like->object_type = 'post';
        $like->save();

        // update the likes
        $post->likes++;
        $post->save();

        Log::info('User '.$id.' is liking '.$post->name);

        flash()->success('Success',  'You are now liking the selected post.');

        return back();
    }

    /**
     * Mark user as unliking the post.
     *
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function unlike($id, Request $request)
    {

        // check if there is a logged in user
        if (!$this->user)
        {
            flash()->error('Error',  'No user is logged in.');
            return back();
        };

        if (!$post = Post::find($id))
        {
            flash()->error('Error',  'No such post');
            return back();
        };

        // update the likes
        $post->likes--;
        $post->save();

        // delete the like
        $response = Like::where('object_id','=', $id)->where('user_id','=',$this->user->id)->where('object_type','=','post')->first();
        $response->delete();

        flash()->success('Success',  'You are no longer liking the post.');

        return back();
    }

}
