@extends('layouts.app-tw')

@section('title', 'Forum')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">Forum</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <x-ui.button variant="secondary" href="{{ url('/threads/all') }}">
                <i class="bi bi-list-ul mr-2"></i>All Threads
            </x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('threads.index') }}">
                <i class="bi bi-grid mr-2"></i>Paginated
            </x-ui.button>
            <x-ui.button variant="default" href="{{ route('threads.create') }}">
                <i class="bi bi-plus-lg mr-2"></i>New Thread
            </x-ui.button>
        </div>
    </div>

    @if (isset($thread) && $thread)
        <div class="card-tw mb-6">
            <div class="p-6 border-b border-border">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-foreground">
                            <a href="{{ route('threads.show', $thread->id) }}" class="hover:text-primary transition-colors">
                                {{ $thread->name }}
                            </a>
                            @if ($signedIn && $thread->ownedBy($user))
                                <a href="{{ route('threads.edit', ['id' => $thread->id]) }}" class="text-muted-foreground hover:text-primary ml-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                        </h2>
                        <div class="flex flex-wrap gap-4 mt-2 text-sm text-muted-foreground">
                            <span><i class="bi bi-folder mr-1"></i>{{ $thread->thread_category ?? 'General' }}</span>
                            <span><i class="bi bi-person mr-1"></i>{{ $thread->user->name ?? 'User deleted' }}</span>
                            <span><i class="bi bi-chat mr-1"></i>{{ $thread->postCount }} posts</span>
                            <span><i class="bi bi-eye mr-1"></i>{{ $thread->views }} views</span>
                            <span><i class="bi bi-clock mr-1"></i>{{ $thread->lastPostAt->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                @unless ($thread->series->isEmpty())
                    <div class="mt-3">
                        <span class="text-sm text-muted-foreground">Series:</span>
                        @foreach ($thread->series as $series)
                            <a href="/threads/series/{{ urlencode($series->slug) }}" class="badge-tw badge-secondary-tw text-xs">
                                {{ $series->name }}
                            </a>
                        @endforeach
                    </div>
                @endunless

                @unless ($thread->entities->isEmpty())
                    <div class="mt-2">
                        <span class="text-sm text-muted-foreground">Related:</span>
                        @foreach ($thread->entities as $entity)
                            <a href="/threads/related-to/{{ urlencode($entity->slug) }}" class="badge-tw badge-secondary-tw text-xs">
                                {{ $entity->name }}
                            </a>
                        @endforeach
                    </div>
                @endunless

                @unless ($thread->tags->isEmpty())
                    <div class="mt-2">
                        <span class="text-sm text-muted-foreground">Tags:</span>
                        @foreach ($thread->tags as $tag)
                            <a href="/threads/tag/{{ urlencode($tag->name) }}" class="badge-tw badge-primary-tw text-xs">
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endunless
            </div>

            <div class="p-6">
                <div class="prose dark:prose-invert max-w-none">
                    {!! $thread->body !!}
                </div>

                @if ($signedIn && $thread->ownedBy($user))
                    <div class="mt-4">
                        <form action="{{ route('threads.destroy', ['id' => $thread->id]) }}" method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this thread?')">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="destructive" size="sm">
                                <i class="bi bi-trash mr-1"></i>Delete
                            </x-ui.button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        @include('posts.list-tw', ['thread' => $thread, 'posts' => $thread->posts])

        <div class="card-tw p-6 mt-6">
            @if ($signedIn)
                <h3 class="text-lg font-semibold text-foreground mb-4">
                    Reply as <span class="text-primary">{{ $user->name }}</span>
                </h3>
                <form method="POST" action="{{ $thread->path().'/posts' }}">
                    @csrf
                    <x-ui.form-group name="body" label="">
                        <textarea name="body" id="body" rows="5" class="input-tw w-full"
                            placeholder="Have something to say?"></textarea>
                    </x-ui.form-group>
                    <x-ui.button type="submit" variant="default">
                        <i class="bi bi-send mr-2"></i>Post Reply
                    </x-ui.button>
                </form>
            @else
                <p class="text-center text-muted-foreground">
                    Please <a href="{{ url('/login') }}" class="text-primary hover:underline">sign in</a> to participate in this discussion.
                </p>
            @endif
        </div>
    @else
        <div class="card-tw p-8 text-center text-muted-foreground">
            <i class="bi bi-chat-square-text text-4xl mb-3"></i>
            <p>No thread found</p>
        </div>
    @endif
</div>
@stop
