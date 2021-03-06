<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Photo;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Visibility;
use App\Models\Tag;

class PhotosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $photos = Photo::get();

        return view('photos.index', compact('photos'));
    }

    /**
     * Show a form to create a new Article.
     **/
    public function create()
    {
        $tags = Tag::pluck('name', 'id');
        $entities = Entity::pluck('name', 'id');

        return view('photos.create', compact('tags', 'entities'));
    }

    public function show(Photo $photo)
    {
        return view('photos.show', compact('photo'));
    }

    public function store(Request $request, Photo $photo)
    {
        $input = $request->all();

        $photo = $photo->create($input);

        $photo->entities()->attach($request->input('entity_list'));

        Session::flash('flash_message', 'Your photo has been created!');

        return redirect()->route('photos.index');
    }

    public function edit(Photo $photo)
    {
        $this->middleware('auth');

        $type = EntityType::where('name', 'Venue')->first();
        $venues = array_merge(['' => ''], DB::table('entities')->where('entity_type_id', $type->id)->orderBy('name', 'ASC')->pluck('name', 'id'));
        $visibilities = array_merge(['' => ''], Visibility::pluck('name', 'id'));
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id');
        $entities = Entity::orderBy('name', 'ASC')->pluck('name', 'id');

        return view('photos.edit', compact('photo', 'venues', 'visibilities', 'tags', 'entities'));
    }

    public function update(Photo $photo, Request $request)
    {
        $photo->fill($request->input())->save();

        $photo->entities()->sync($request->input('entity_list', []));

        \Session::flash('flash_message', 'Your photo has been updated!');

        return redirect('photos');
    }

    public function destroy($id)
    {
        $photo = Photo::findOrFail($id)->delete();

        flash('Success', 'Your photo has been deleted');

        return back();
    }

    public function setPrimary($id)
    {
        $photo = Photo::findOrFail($id);

        // get anything linked to this photo
        $users = $photo->users;

        foreach ($users as $user) {
            foreach ($user->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $entities = $photo->entities;
        foreach ($entities as $entity) {
            foreach ($entity->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $events = $photo->events;
        foreach ($events as $event) {
            foreach ($event->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $series = $photo->series;
        foreach ($series as $s) {
            foreach ($s->photos as $p) {
                $p->is_primary = 0;
                $p->save();
            };
        };

        $photo->is_primary = 1;
        $photo->save();

        flash('Success', 'The primary photo was updated.');

        return back();
    }

    public function unsetPrimary($id)
    {
        $photo = Photo::findOrFail($id);

        $photo->is_primary = 0;
        $photo->save();

        flash('Success', 'The primary photo was unset.');

        return back();
    }
}
