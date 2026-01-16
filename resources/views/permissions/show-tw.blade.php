@extends('layouts.app-tw')

@section('title', 'Permission View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Permission</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_permission')
                <x-ui.button variant="default" href="{{ route('permissions.edit', ['permission' => $permission->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('permissions.index') }}">
                <i class="bi bi-arrow-left mr-2"></i>Return to list
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $permission->label }}</h2>

        <div class="grid gap-4">
            @if ($permission->name)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Name</label>
                    <p class="text-foreground mt-1">{{ $permission->name }}</p>
                </div>
            @endif

            @if ($permission->level)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Level</label>
                    <p class="text-foreground mt-1">{{ $permission->level }}</p>
                </div>
            @endif
        </div>

        @unless ($permission->groups->isEmpty())
            <div class="mt-6">
                <label class="text-sm font-medium text-muted-foreground">Groups</label>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach ($permission->groups as $group)
                        <a href="/groups/{{ $group->id }}" class="badge-tw badge-secondary-tw">
                            {{ $group->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endunless

        @can('edit_permission')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this permission?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Permission
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
