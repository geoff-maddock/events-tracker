@extends('layouts.app-tw')

@section('title', 'Thread Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-foreground mb-2">Edit Thread</h1>
        <div class="text-sm text-muted-foreground">
            @include('threads.crumbs', ['slug' => $thread->slug ?: $thread->id])
        </div>
    </div>

    <div class="bg-card rounded-lg border border-border shadow-sm p-6">
        <form method="POST" action="{{ route('threads.update', $thread->id) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            @include('threads.form', ['action' => 'update'])
        </form>

        <!-- Delete Button -->
        @if ($user && ($thread->ownedBy($user) || $user->hasGroup('super_admin')))
        <div class="mt-6 pt-6 border-t border-border">
            <form method="POST" action="{{ route('threads.destroy', $thread->id) }}" onsubmit="return confirm('Are you sure you want to delete this thread? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-md hover:bg-destructive/90 transition-colors">
                    <i class="bi bi-trash mr-2"></i>
                    Delete Thread
                </button>
            </form>
        </div>
        @endif
    </div>

    <div class="mt-6">
        <x-ui.button variant="ghost" href="{{ route('threads.index') }}">
            <i class="bi bi-arrow-left mr-2"></i>
            Return to list
        </x-ui.button>
    </div>
</div>

@stop
