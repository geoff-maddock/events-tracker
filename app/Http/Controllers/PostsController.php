<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Entity;
use App\Http\Requests\PostRequest;
use App\Models\Like;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\TagType;
use App\Models\Thread;
use App\Models\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class PostsController extends Controller
{
    protected Post $post;

    protected string $prefix;

    protected int $rpp;

    protected int $page;

    protected int $defaultRpp;

    protected string $defaultSortBy;

    protected string $defaultSortOrder;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected array $defaultCriteria;

    protected bool $hasFilter;

    protected array $filters;

    protected array $criteria;

    public function __construct(Post $post)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // prefix for session storage
        $this->prefix = 'app.posts.';

        // default list variables - move to function that set from session or default
        $this->defaultRpp = 10;
        $this->defaultSortBy = 'created_at';
        $this->defaultSortOrder = 'desc';

        $this->rpp = 10;
        $this->page = 1;
        $this->sort = ['created_at', 'desc'];
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->defaultCriteria = [];
        $this->hasFilter = 1;

        $post = $post;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response | View | string
     */
    public function index()
    {
        // if the gate does not allow this user to show a forum redirect to home
        if (Gate::denies('show_forum')) {
            flash()->error('Unauthorized', 'Your cannot view the forum');

            return redirect()->back();
        }

        $posts = Post::orderBy('created_at', 'desc')->paginate($this->rpp);
        $posts->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        return view('posts.index', compact('posts'));
    }

    /**
     * Update the page list parameters from the request.
     *
     */
    protected function updatePaging(Request $request): void
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        }

        // set sort direction
        if ($request->input('sort_direction')) {
            $this->sortOrder = $request->input('sort_direction');
        }

        if (!empty($request->input('rpp')) && is_numeric($request->input('rpp'))) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Display a listing of posts by tag.
     * @return View
     */
    public function indexTags(Request $request, string $tag)
    {
        $hasFilter = true;

        // updates sort, rpp from request
        $this->updatePaging($request);

        $tag = urldecode($tag);

        $posts = Post::getByTag(ucfirst($tag))
                    ->orderBy('created_at', 'ASC')
                    ->paginate($this->rpp);

        return view('posts.index')
                    ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'tag' => $tag, 'hasFilter' => $hasFilter])
                    ->with(compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response | View | string
     */
    public function create()
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $series = Series::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('posts.create', compact('visibilities', 'tags', 'entities', 'series'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     *
     * @internal param Request $request
     */
    public function store(Request $request, Thread $thread)
    {
        $msg = '';

        // TODO change this to use the trust_post permission to allow html
        if (auth()->id() === config('app.superuser')) {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!DB::table('tags')->where('id', $tag)->get()) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tagType()->associate(TagType::find(1));
                $newTag->save();

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $thread->addPost([
            'body' => request('body'),
            'created_by' => auth()->id(),
            'visibility_id' => 1,
            'allow_html' => $allow_html,
        ]);

        $post = Post::where('thread_id', '=', $thread->id)->orderBy('id', 'DESC')->first();

        $post->tags()->sync($syncArray);

        // here, notify anybody following the thread
        $this->notifyFollowing($post);

        // add to activity log
        Activity::log($post, $this->user, 1);

        return back();
    }

    /**
     * @param Post $post
     *
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
        $users = [];

        // notify users who are following this thread
        foreach ($thread->followers() as $user) {
            // if the user hasn't already been notified, then email them
            if (!array_key_exists($user->id, $users)) {
                Mail::send('emails.following-thread-post', ['user' => $user, 'post' => $post, 'thread' => $thread, 'object' => $thread, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $post, $thread, $reply_email, $site) {
                    $m->from($reply_email, $site);

                    $m->to($user->email, $user->name)->subject($site . ': New post by ' . $post->user->name . ' in thread ' . $thread->name);
                });
                $users[$user->id] = $thread->name;
            }
        }

        // notify users following any tags related to the thread

        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $thread, $tag, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $tag->name . ' :: ' . $thread->created_at->format('D F jS') . ' ' . $thread->name);
                    });
                    $users[$user->id] = $tag->name;
                }
            }
        }

        // notify users following any of the series
        $seriess = $thread->series()->get();

        foreach ($seriess as $series) {
            foreach ($series->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following-thread', ['user' => $user, 'thread' => $thread, 'object' => $series, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $thread, $series, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $series->name . ' :: ' . $thread->created_at->format('D F jS') . ' ' . $thread->name);
                    });
                    $users[$user->id] = $series->name;
                }
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     *
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
        ++$post->views;
        $post->save();

        $route = route('threads.show', ['thread' => $post->thread_id]) . '#post-' . $post->id;

        return redirect($route);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        $this->middleware('auth');

        $visibilities = ['' => ''] + Visibility::pluck('name', 'id')->all();
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('posts.edit', compact('post', 'visibilities', 'tags', 'entities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @internal param int $id
     */
    public function update(Post $post, PostRequest $request)
    {
        $msg = '';

        $post->fill($request->input())->save();

        if (!$post->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!DB::table('tags')->where('id', $tag)->get()) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tagType()->associate(TagType::find(1));
                $newTag->save();

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $post->tags()->sync($syncArray);
        $post->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($post, $this->user, 2);

        flash('Success', 'Your post has been updated');

        return redirect()->route('threads.show', ['thread' => $post->thread_id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     *
     * @internal param int $id
     */
    public function destroy(Post $post)
    {
        $id = $post->thread_id;
        $thread = $post->thread;

        if ($this->user->cannot('destroy', $post)) {
            flash('Error', 'Your are not authorized to delete the post.');

            return redirect()->route('threads.show', ['thread' => $id]);
        }

        // add to activity log
        Activity::log($post, $this->user, 3);

        $post->delete();

        flash()->success('Success', 'Your post has been deleted!');

        return redirect()->route('threads.show', ['thread' => $id]);
    }

    /**
     * Mark user as liking the post.
     *
     * @return Response
     */
    public function like($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$post = Post::find($id)) {
            flash()->error('Error', 'No such post');

            return back();
        }

        // add the like response
        $like = new Like();
        $like->object_id = $id;
        $like->user()->associate($this->user);
        $like->object_type = 'post';
        $like->save();

        // update the likes
        ++$post->likes;
        $post->save();

        Log::info('User ' . $id . ' is liking ' . $post->name);

        flash()->success('Success', 'You are now liking the selected post.');

        return back();
    }

    /**
     * Mark user as unliking the post.
     *
     * @return Response
     */
    public function unlike(int $id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$post = Post::find($id)) {
            flash()->error('Error', 'No such post');

            return back();
        }

        // update the likes
        --$post->likes;
        $post->save();

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'post')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer liking the post.');

        return back();
    }

    protected function unauthorized(PostRequest $request): RedirectResponse
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }
}
