<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Comment;
use App\Models\Entity;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    protected Entity $entity;

    protected array $rules = [
        'message' => ['required', 'min:3'],
    ];

    public function __construct(Entity $entity)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->entity = $entity;

        parent::__construct();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Entity $entity, Event $event): View
    {
        $object = null;
        $type = null;

        if (isset($entity->id)) {
            $object = $entity;
            $type = 'entities';
        }

        if (isset($event->id)) {
            $object = $event;
            $type = 'events';
        }

        return view('comments.create-tw', compact('object', 'type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Entity $entity, Event $event): RedirectResponse
    {
        $msg = '';
        $type = null;

        // get the request
        $input = $request->all();

        if (isset($entity->id)) {
            $input['commentable_type'] = 'entity';
            $input['commentable_id'] = $entity->id;
            $type = 'entities';
        }

        if (isset($event->id)) {
            $input['commentable_type'] = 'event';
            $input['commentable_id'] = $event->id;
            $type = 'events';
        }

        $this->validate($request, $this->rules);

        $comment = Comment::create($input);
        $comment->save();

        flash()->success('Success', 'Your comment has been created');

        return redirect()->route($type.'.show', $comment->commentable->getRouteKey());
    }

    /**
     * Display the specified comment.
     */
    public function show(Entity $entity, Comment $comment): View
    {
        return view('comments.show', compact('entity', 'comment'));
    }

    /**
     * Show the form for editing the specified comment.
     */
    public function edit(Entity $entity, Comment $comment): View
    {
        $object = $comment->commentable;
        $entity = null;
        $event = null;
        $type = null;

        if (get_class($object) == Entity::class) {
            $entity = $object;
            $type = 'entities';
        }

        if (get_class($object) == Event::class) {
            $event = $object;
            $type = 'events';
        }

        return view('comments.edit-tw', compact('entity', 'object', 'event', 'comment', 'type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ContactRequest $request, Entity $entity, Comment $comment): RedirectResponse
    {
        $msg = '';

        $comment->fill($request->input())->save();

        flash('Success', 'Your comment has been updated');

        return redirect()->route('entities.show', $entity->getRouteKey());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity, Comment $comment): RedirectResponse
    {
        $comment->delete();

        \Session::flash('flash_message', 'Your comment has been deleted!');

        flash()->success('Success', 'Your comment deleted');

        return back();
    }
}
