<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactsController extends Controller
{
    protected array $rules = [
        'name' => ['required', 'min:3'],
        'visibility_id' => ['required'],
    ];

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        parent::__construct();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Entity $entity, Contact $contact): View
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.create', compact('entity', 'contact', 'visibilities'));
    }

    /**
     * Store a newly created resource in storage.
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
     */
    public function show(Entity $entity, Contact $contact): View
    {
        return view('contacts.show', compact('entity', 'contact'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity, Contact $contact): View
    {
        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.edit', compact('entity', 'contact', 'visibilities'));
    }

    /**
     * Update the specified resource in storage.
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
     * @throws \Exception
     */
    public function destroy(Entity $entity, Contact $contact): RedirectResponse
    {
        $contact->delete();

        flash()->success('Success', 'Your contacts has been deleted!');

        return redirect()->route('entities.show', $entity->slug);
    }
}
