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
        // per-entity ownership is checked in each action via EntityPolicy::update
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'destroy']]);

        parent::__construct();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Entity $entity, Contact $contact): View
    {
        $this->authorize('update', $entity);

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.create-tw', compact('entity', 'contact', 'visibilities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Entity $entity): RedirectResponse
    {
        $this->authorize('update', $entity);

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
        return view('contacts.show-tw', compact('entity', 'contact'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity, Contact $contact): View
    {
        $this->authorize('update', $entity);
        $this->ensureBelongsToEntity($entity, $contact);

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('contacts.edit-tw', compact('entity', 'contact', 'visibilities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ContactRequest $request, Entity $entity, Contact $contact): RedirectResponse
    {
        $this->authorize('update', $entity);
        $this->ensureBelongsToEntity($entity, $contact);

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
        $this->authorize('update', $entity);
        $this->ensureBelongsToEntity($entity, $contact);

        $contact->delete();

        flash()->success('Success', 'Your contacts has been deleted!');

        return redirect()->route('entities.show', $entity->slug);
    }

    /**
     * A contact reached through another entity's URL must not be editable by that entity's owner.
     */
    protected function ensureBelongsToEntity(Entity $entity, Contact $contact): void
    {
        abort_unless($entity->contacts()->whereKey($contact->id)->exists(), 404);
    }
}
