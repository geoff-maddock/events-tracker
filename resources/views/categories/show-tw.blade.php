@extends('layouts.app-tw')

@section('title', 'Category View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Category</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_category')
                <x-ui.button variant="default" href="{{ route('categories.edit', ['category' => $category->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('categories.index') }}">
                <i class="bi bi-arrow-left mr-2"></i>Return to list
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $category->name }}</h2>

        @if ($category->slug)
            <p class="text-sm text-muted-foreground mb-4">Slug: {{ $category->slug }}</p>
        @endif

        <div class="grid gap-4">
            @if ($category->description)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Description</label>
                    <p class="text-foreground mt-1">{{ $category->description }}</p>
                </div>
            @endif
        </div>

        @can('edit_category')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('categories.destroy', $category->id) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this category?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Category
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
