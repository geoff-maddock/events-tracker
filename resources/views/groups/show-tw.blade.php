@extends('layouts.app-tw')

@section('title', 'Group View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Group</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_group')
                <x-ui.button variant="default" href="{{ route('groups.edit', ['group' => $group->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('groups.index') }}">
                <i class="bi bi-arrow-left mr-2"></i>Return to list
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <h2 class="text-2xl font-semibold text-foreground mb-4">{{ $group->label }}</h2>

        <div class="grid gap-4">
            @if ($group->name)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Name</label>
                    <p class="text-foreground mt-1">{{ $group->name }}</p>
                </div>
            @endif

            @if ($group->level)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Level</label>
                    <p class="text-foreground mt-1">{{ $group->level }}</p>
                </div>
            @endif
        </div>

        @unless ($group->permissions->isEmpty())
            <div class="mt-6">
                <label class="text-sm font-medium text-muted-foreground">Permissions</label>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach ($group->permissions as $permission)
                        <a href="/permissions/{{ $permission->id }}" class="badge-tw badge-secondary-tw">
                            {{ $permission->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endunless

        @unless ($group->users->isEmpty())
            <div class="mt-6">
                <label class="text-sm font-medium text-muted-foreground">Users</label>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach ($group->users as $user)
                        <a href="/users/{{ $user->id }}" class="badge-tw badge-primary-tw">
                            {{ $user->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endunless

        @can('edit_group')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('groups.destroy', $group->id) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this group?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Group
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
