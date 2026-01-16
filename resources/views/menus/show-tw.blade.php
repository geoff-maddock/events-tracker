@extends('layouts.app-tw')

@section('title', $menu->label)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Menu</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_menu')
                <x-ui.button variant="default" href="{{ route('menus.edit', ['menu' => $menu->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('menus.index') }}">
                <i class="bi bi-arrow-left mr-2"></i>Return to list
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $menu->label }}</h2>

        <div class="grid gap-4">
            @if ($menu->name)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Name</label>
                    <p class="text-foreground mt-1">{{ $menu->name }}</p>
                </div>
            @endif

            @if ($menu->slug)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Slug</label>
                    <p class="text-foreground mt-1">{{ $menu->slug }}</p>
                </div>
            @endif
        </div>

        @can('edit_menu')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('menus.destroy', $menu->id) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this menu?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Menu
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
