@extends('layouts.app-tw')

@section('title', 'Contact View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Contact</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_entity')
                <x-ui.button variant="default" href="{{ route('entities.contacts.edit', ['entity' => $entity->slug, 'contact' => $contact->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('entities.show', ['entity' => $entity->slug]) }}">
                <i class="bi bi-arrow-left mr-2"></i>Back to Entity
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $contact->name }}</h2>

        <div class="grid gap-4">
            @if ($contact->email)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Email</label>
                    <p class="text-foreground mt-1">
                        <a href="mailto:{{ $contact->email }}" class="text-primary hover:underline">
                            <i class="bi bi-envelope mr-1"></i>{{ $contact->email }}
                        </a>
                    </p>
                </div>
            @endif

            @if ($contact->phone)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Phone</label>
                    <p class="text-foreground mt-1">
                        <a href="tel:{{ $contact->phone }}" class="text-primary hover:underline">
                            <i class="bi bi-telephone mr-1"></i>{{ $contact->phone }}
                        </a>
                    </p>
                </div>
            @endif

            @if ($contact->type)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Type</label>
                    <p class="text-foreground mt-1">{{ $contact->type }}</p>
                </div>
            @endif

            @if ($contact->other)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Other Information</label>
                    <p class="text-foreground mt-1">{{ $contact->other }}</p>
                </div>
            @endif

            @if ($contact->visibility)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Visibility</label>
                    <p class="text-foreground mt-1">{{ $contact->visibility->name }}</p>
                </div>
            @endif

            <div>
                <label class="text-sm font-medium text-muted-foreground">Entity</label>
                <p class="mt-1">
                    <a href="{{ route('entities.show', ['entity' => $entity->slug]) }}" class="text-primary hover:underline">
                        {{ $entity->name }}
                    </a>
                </p>
            </div>
        </div>

        @can('edit_entity')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('entities.contacts.destroy', ['entity' => $entity->slug, 'contact' => $contact->id]) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this contact?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Contact
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
