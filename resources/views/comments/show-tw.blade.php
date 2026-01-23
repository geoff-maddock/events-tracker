@extends('layouts.app-tw')

@section('title', 'Comment View')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Comment</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            @can('edit_entity')
                <x-ui.button variant="default" href="{{ route('entities.comments.edit', ['entity' => $entity->slug, 'comment' => $comment->id]) }}">
                    <i class="bi bi-pencil mr-2"></i>Edit
                </x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('entities.show', ['entity' => $entity->slug]) }}">
                <i class="bi bi-arrow-left mr-2"></i>Back to Entity
            </x-ui.button>
        </div>
    </div>

    <div class="card-tw p-6">
        <div class="grid gap-4">
            <div>
                <label class="text-sm font-medium text-muted-foreground">Message</label>
                <div class="prose dark:prose-invert max-w-none mt-2 p-4 bg-muted/50 rounded-lg">
                    {{ $comment->message }}
                </div>
            </div>

            @if ($comment->commentable)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Related To</label>
                    <p class="text-foreground mt-1">
                        {{ class_basename($comment->commentable_type) }}: {{ $comment->commentable->name ?? $comment->commentable->title ?? 'ID: ' . $comment->commentable_id }}
                    </p>
                </div>
            @endif

            @if ($comment->created_by)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Created By</label>
                    <p class="text-foreground mt-1">
                        @if ($comment->user)
                            <a href="/users/{{ $comment->user->id }}" class="text-primary hover:underline">{{ $comment->user->name }}</a>
                        @else
                            Unknown
                        @endif
                    </p>
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

            @if ($comment->created_at)
                <div>
                    <label class="text-sm font-medium text-muted-foreground">Created</label>
                    <p class="text-foreground mt-1">{{ $comment->created_at->format('F j, Y g:i A') }}</p>
                </div>
            @endif
        </div>

        @can('edit_entity')
            <div class="mt-6 pt-6 border-t border-border">
                <form action="{{ route('entities.comments.destroy', ['entity' => $entity->slug, 'comment' => $comment->id]) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this comment?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="destructive">
                        <i class="bi bi-trash mr-2"></i>Delete Comment
                    </x-ui.button>
                </form>
            </div>
        @endcan
    </div>
</div>
@stop
