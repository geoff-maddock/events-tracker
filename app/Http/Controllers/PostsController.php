<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Http\Requests;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Carbon\Carbon;

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
        $visibilities = [''=>''] + Visibility::orderBy('name','ASC')->lists('name', 'id')->all();

        $tags = Tag::orderBy('name','ASC')->lists('name','id')->all();
        $entities = Entity::orderBy('name','ASC')->lists('name','id')->all();
        $series = Series::orderBy('name','ASC')->lists('name','id')->all();


        return view('posts.create', compact('visibilities','tags','entities','series'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Thread $thread)
    {
        $thread->addPost([
            'body' => request('body'),
            'created_by' => auth()->id(),
            'visibility_id' => 1,
            'allow_html' => $thread->allow_html
            ]);

        return back();
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
        $post->views++;
        $post->save();

        return view('posts.show', compact('post'));
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

        $visibilities = [''=>''] + Visibility::lists('name', 'id')->all();
        $tags = Tag::orderBy('name','ASC')->lists('name','id')->all();
        $entities = Entity::orderBy('name','ASC')->lists('name','id')->all();
        $series = [''=>''] + Series::lists('name', 'id')->all();

        return view('posts.edit', compact('post', 'visibilities', 'tags','entities','series'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
