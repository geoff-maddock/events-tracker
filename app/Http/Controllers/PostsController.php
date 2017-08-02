<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Carbon\Carbon;

use Gate;
use DB;
use Log;
use Mail;
use App\Post;
use App\Thread;
use App\Event;
use App\Entity;
use App\ThreadCategory;
use App\Series;
use App\Tag;
use App\Visibility;
use App\Activity;

class PostsController extends Controller
{

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Post $post, Thread $thread)
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

        // here, notify anybody following the thread
        $this->notifyFollowing($post);

        // add to activity log
        Activity::log($post, $this->user, 1);

        return back();
    }

    /**
     * @param $thread
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

        // notify users following any tags related to the thread
        foreach ($tags as $tag)
        {
            foreach ($tag->followers() as $user)
            {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users))
                {
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $tag, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
                    });
                    $users[$user->id] = $tag->name;
                };
            };
        };

        // notify users following any of the series
        $seriess = $event->series()->get();

        foreach ($seriess as $series)
        {
            foreach ($series->followers() as $user)
            {

                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users))
                {
                    Mail::send('emails.following-thread', ['user' => $user, 'event' => $event, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $tag, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$entity->name.' :: '.$event->start_at->format('D F jS').' '.$event->name);
                    });
                    $users[$user->id] = $entity->name;
                };
            };
        };

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
     * @param  int  $id
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        $id = $post->thread_id;
        $thread = $post->thread;

        // add to activity log
        Activity::log($post, $this->user, 3);

        $post->delete();

        flash()->success('Success', 'Your post has been deleted!');

       return redirect()->route('threads.show',['id' => $id]);

    }
}
