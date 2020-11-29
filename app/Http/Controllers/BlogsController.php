<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Blog;
use App\ContentType;
use App\Entity;
use App\Http\Requests\BlogRequest;
use App\Like;
use App\Menu;
use App\Tag;
use App\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class BlogsController extends Controller
{
    protected int $rpp;
    protected string $sortBy;
    protected string $sortDirection;

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // default list variables
        $this->rpp = 15;
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // if the gate does not allow this user to show a blog redirect to home
//        if (Gate::denies('show_blog')) {
//            flash()->error('Unauthorized', 'Your cannot view the blog');
//
//            return redirect()->back();
//        }

        $blogs = Blog::orderBy('created_at', 'desc')->paginate($this->rpp);
        $blogs->filter(function ($e) {
            return ($e->visibility && 'Public' === $e->visibility->name) || ($this->user && $e->created_by === $this->user->id);
        });

        return view('blogs.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortDirection' => $this->sortDirection])
            ->with(compact('blogs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $menus = ['' => ''] + Menu::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $contentTypes = ['' => ''] + ContentType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('blogs.create', compact('visibilities', 'tags', 'entities', 'menus', 'contentTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param Request $request
     */
    public function store(BlogRequest $request, Blog $blog)
    {
        // TODO change this to use the trust_blog permission to allow html
        if (auth()->id() === config('app.superuser')) {
            $allow_html = 1;
        } else {
            $allow_html = 0;
        }

        $msg = '';

        $input = $request->all();

        $blog = $blog->create($input);

        flash()->success('Success', 'Your blog has been created');

        // here, notify anybody following the blog
        // $this->notifyFollowing($blog);

        // add to activity log
        Activity::log($blog, $this->user, 1);

        return redirect()->route('blogs.index');
    }

    /**
     * @param Blog $blog
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($blog)
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $blog->tags()->get();
        $users = [];

        // notify users following any tags related to the blog

        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following-thread', ['user' => $user, 'blog' => $blog, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $blog, $tag, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site.': '.$tag->name.' :: '.$blog->created_at->format('D F jS').' '.$blog->name);
                    });
                    $users[$user->id] = $tag->name;
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
    public function show(Blog $blog)
    {
        // if the gate does not allow this user to show a forum redirect to home
//        if (Gate::denies('show_blog')) {
//            flash()->error('Unauthorized', 'Your cannot view the blog');
//
//            return redirect()->back();
//        }

        return view('blogs.show', compact('blog'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Blog $blog)
    {
        $this->middleware('auth');

        $visibilities = ['' => ''] + Visibility::pluck('name', 'id')->all();
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $menus = ['' => ''] + Menu::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $contentTypes = ['' => ''] + ContentType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('blogs.edit', compact('blog', 'visibilities', 'tags', 'entities', 'menus', 'contentTypes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BlogRequest|Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function update(Blog $blog, BlogRequest $request)
    {
        $msg = '';

        $blog->fill($request->input())->save();

        if (!$blog->ownedBy($this->user)) {
            $this->unauthorized($request);
        }

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!DB::table('tags')->where('id', $tag)->get()) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        $blog->tags()->sync($syncArray);
        $blog->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($blog, $this->user, 2);

        flash('Success', 'Your blog has been updated');

        return redirect()->route('blogs.index');
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
    public function destroy(Blog $blog)
    {
        if ($this->user->cannot('destroy', $blog)) {
            flash('Error', 'Your are not authorized to delete the blog.');

            return redirect()->route('blogs.index');
        }

        // add to activity log
        Activity::log($blog, $this->user, 3);

        $blog->delete();

        flash()->success('Success', 'Your blog has been deleted!');

        return redirect()->route('blogs.index');
    }

    /**
     * Mark user as liking the blog.
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

        if (!$blog = Blog::find($id)) {
            flash()->error('Error', 'No such blog');

            return back();
        }

        // add the like response
        $like = new Like();
        $like->object_id = $id;
        $like->user_id = $this->user->id;
        $like->object_type = 'blog';
        $like->save();

        // update the likes
        ++$blog->likes;
        $blog->save();

        Log::info('User '.$id.' is liking '.$blog->name);

        flash()->success('Success', 'You are now liking the selected blog.');

        return back();
    }

    /**
     * Mark user as unliking the blog.
     *
     * @param $id
     *
     * @return Response
     */
    public function unlike($id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$blog = Blog::find($id)) {
            flash()->error('Error', 'No such blog');

            return back();
        }

        // update the likes
        --$blog->likes;
        $blog->save();

        // delete the like
        $response = Like::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'blog')->first();
        $response->delete();

        flash()->success('Success', 'You are no longer liking the blog.');

        return back();
    }

    protected function unauthorized(Request $request): RedirectResponse
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

}
