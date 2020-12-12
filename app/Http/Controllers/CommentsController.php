<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Comment;

class CommentsController extends Controller
{
    protected Entity $entity;

    protected $rules = [
        'message' => ['required', 'min:3'],
    ];

    public function __construct(Entity $entity)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->entity = $entity;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Entity 		$entity
     * @return Response
     */
    public function index(Entity $entity)
    {
        return view('comments.index', compact('entity'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Entity 		$entity
     * @return Response
     */
    public function create(Entity $entity, Event $event)
    {
        $object = null;
        $type = null;

        if (isset($entity->id)) {
            $object = $entity;
            $type = 'entities';
        };

        if (isset($event->id)) {
            $object = $event;
            $type = 'events';
        }

        return view('comments.create', compact('object', 'type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request 			$request
     * @param  Entity 		$entity
     * @return Response
     */
    public function store(Request $request, Entity $entity, Event $event)
    {
        $msg = '';
        $type = null;

        // get the request
        $input = $request->all();

        if (isset($entity->id)) {
            $input['commentable_type'] = 'entity';
            $input['commentable_id'] = $entity->id;
            $type = 'entities';
        };

        if (isset($event->id)) {
            $input['commentable_type'] = 'event';
            $input['commentable_id'] = $event->id;
            $type = 'events';
        };

        $this->validate($request, $this->rules);

        $comment = Comment::create($input);
        $comment->save();

        flash()->success('Success', 'Your comment has been created');

        return redirect()->route($type . '.show', $comment->commentable->getRouteKey());
    }

    /**
     * Display the specified resource.
     *
     * @param  Entity 		$entity
     * @param  Comment  	$comment
     * @return Response
     */
    public function show(Entity $entity, Comment $comment)
    {
        return view('comments.show', compact('entity', 'comment'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param String $id
     * @param  Comment $comment
     * @return Response
     */
    public function edit($id, Comment $comment)
    {
        $object = $comment->commentable;
        $entity = null;
        $event = null;
        $type = null;

        if (get_class($object) == Entity::class) {
            $entity = $object;
            $type = 'entities';
        };

        if (get_class($object) == Event::class) {
            $event = $object;
            $type = 'events';
        }

        return view('comments.edit', compact('entity', 'object', 'event', 'comment', 'type'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request 			$request
     * @param  Entity 		$entity
     * @param  Comment  	$comment
     * @return Response
     */
    public function update(Request $request, Entity $entity, Comment $comment)
    {
        $msg = '';

        $comment->fill($request->input())->save();

        \Session::flash('flash_message', 'Your comment has been updated!');

        return redirect()->route('entities.show', $entity->getRouteKey());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Entity $entity
     * @param  Comment $comment
     * @return Response
     * @throws \Exception
     */
    public function destroy(Entity $entity, Comment $comment)
    {
        $comment->delete();

        \Session::flash('flash_message', 'Your comment has been deleted!');

        flash()->success('Success', 'Your comment deleted');

        return back();
    }
}
