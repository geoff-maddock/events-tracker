<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Entity;
use App\Models\Contact;
use App\Models\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
     */
    public function index(): View
    {
        $contacts = Contact::all();

        return view('contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create(Entity $entity, Contact $contact): View
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.create', compact('entity', 'contact', 'visibilities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @param  Entity $entity
     */
    public function store(Request $request, Entity $entity): RedirectResponse
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
     */
    public function show(Entity $entity, Contact $contact): View
    {
        return view('contacts.show', compact('entity', 'contact'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Entity $entity
     * @param  Contact $contact
     */
    public function edit(Entity $entity, Contact $contact): View
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.edit', compact('entity', 'contact', 'visibilities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ContactRequest $request
     * @param  Entity $entity
     * @param  Contact $contact
     */
    public function update(ContactRequest $request, Entity $entity, Contact $contact): RedirectResponse
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
     * @throws \Exception
     */
    public function destroy(Entity $entity, Contact $contact): RedirectResponse
    {
        $contact->delete();

        flash()->success('Success', 'Your contacts has been deleted!');

        return redirect()->route('entities.show', $entity->slug);
    }
}
