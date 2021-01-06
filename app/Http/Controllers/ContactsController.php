<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Entity;
use App\Models\Contact;
use App\Models\Visibility;

class ContactsController extends Controller
{
    protected $rules = [
        'name' => ['required', 'min:3'],
        'visibility_id' => ['required']
    ];

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $contacts = Contact::all();

        return view('contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Contact $contact)
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.create', compact('contact', 'visibilities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @param  Entity $entity
     * @return Response
     */
    public function store(Request $request, Entity $entity)
    {
        $msg = '';

        // get the request
        $input = $request->all();
        $input['entity_id'] = $entity->id;

        $this->validate($request, $this->rules);

        $contact = Contact::create($input);

        $entity->contacts()->attach($contact->id);

        flash()->success('Success', 'Your contact has been created');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Display the specified resource.
     *
     * @param  Entity $entity
     * @param  Contact $contact
     * @return Response
     */
    public function show(Entity $entity, Contact $contact)
    {
        return view('contacts.show', compact('entity', 'contact'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Entity $entity
     * @param  Contact $contact
     * @return Response
     */
    public function edit(Entity $entity, Contact $contact)
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.edit', compact('entity', 'contact', 'visibilities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @param  Entity $entity
     * @param  Contact $contact
     * @return Response
     */
    public function update(Request $request, Entity $entity, Contact $contact)
    {
        $msg = '';

        $contact->fill($request->input())->save();

        flash()->success('Success', 'Your contact has been updated!');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Entity $entity
     * @param  Contact $contact
     * @return Response
     * @throws \Exception
     */
    public function destroy(Entity $entity, Contact $contact)
    {
        $contact->delete();

        flash()->success('Success', 'Your contacts has been deleted!');

        return redirect()->route('entities.show', $entity->slug);
    }
}
