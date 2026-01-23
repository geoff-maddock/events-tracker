@extends('layouts.app-tw')

@section('title', 'Link View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Link</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_entity')
                <x-ui.button variant="default" href="{{ route('entities.links.edit', ['entity' => $entity->slug, 'link' => $link->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('entities.show', ['entity' => $entity->slug]) }}">
                <i class="bi bi-arrow-left mr-2"></i>Back to Entity
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $link->title ?? 'Untitled Link' }}</h2>

        <div class="grid gap-4">
            @if ($link->url)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">URL</label>
                    <p class="text-foreground mt-1">
                        <a href="{{ $link->url }}" target="_blank" class="text-primary hover:underline">
                            {{ $link->url }}
                            <i class="bi bi-box-arrow-up-right ml-1 text-xs"></i>
                        </a>
                    </p>
                </div>
            @endif

            @if ($link->text)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Description</label>
                    <p class="text-foreground mt-1">{{ $link->text }}</p>
                </div>
            @endif

            <div>
                <label class="text-sm font-medium text-muted-foreground">Primary Link</label>
                <p class="text-foreground mt-1">
                    @if ($link->is_primary)
                        <span class="badge-tw badge-primary-tw">Yes</span>
                    @else
                        <span class="badge-tw badge-secondary-tw">No</span>
                    @endif
                </p>
            </div>

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
                <form action="{{ route('entities.links.destroy', ['entity' => $entity->slug, 'link' => $link->id]) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this link?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Link
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
