@extends('layouts.app-tw')

@section('title', 'Entity Type View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Entity Type</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_entityType')
                <x-ui.button variant="default" href="{{ route('entity-types.edit', ['entity_type' => $entityType->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('entity-types.index') }}">
                <i class="bi bi-arrow-left mr-2"></i>Return to list
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $entityType->name }}</h2>

        <div class="grid gap-4">
            @if ($entityType->slug)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Slug</label>
                    <p class="text-foreground mt-1">{{ $entityType->slug }}</p>
                </div>
            @endif

            @if ($entityType->short)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Short</label>
                    <p class="text-foreground mt-1">{{ $entityType->short }}</p>
                </div>
            @endif
        </div>

        @can('edit_entityType')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('entity-types.destroy', ['entity_type' => $entityType->id]) }}" method="POST"
                    data-confirm="Are you sure you want to delete this entity type?">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Entity Type
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
