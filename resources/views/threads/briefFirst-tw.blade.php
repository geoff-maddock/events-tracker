<div class="bg-accent rounded-lg p-4 mb-4">
    <div class="flex items-start justify-between mb-2">
        <h4 class="text-lg font-semibold text-foreground">
            <a href="{{ route('threads.show', [$thread->id]) }}" class="hover:text-primary">
                {{ $thread->name }}
            </a>
        </h4>

        <div class="flex items-center gap-2">
            @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
                <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}" title="Edit this thread" class="text-muted-foreground hover:text-foreground">
                    <i class="bi bi-pencil-fill"></i>
                </a>
                
                <form action="{{ route('threads.destroy', $thread) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-muted-foreground hover:text-red-500" onclick="return confirm('Are you sure you want to delete this thread?')" title="Delete the thread">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </form>

                @if (!$thread->is_locked)
                    <a href="{!! route('threads.lock', ['id' => $thread->id]) !!}" title="Lock this thread" class="text-muted-foreground hover:text-foreground">
                        <i class="bi bi-unlock-fill"></i>
                    </a>
                @else
                    <a href="{!! route('threads.unlock', ['id' => $thread->id]) !!}" title="Unlock this thread" class="text-muted-foreground hover:text-foreground">
                        <i class="bi bi-lock-fill"></i>
                    </a>
                @endif
            @endif
            
            @if ($signedIn)
                @if ($follow = $thread->followedBy($user))
                    <a href="{!! route('threads.unfollow', ['id' => $thread->id]) !!}" title="Unfollow" class="text-primary hover:text-primary/90">
                        <i class="bi bi-dash-circle-fill"></i>
                    </a>
                @else
                    <a href="{!! route('threads.follow', ['id' => $thread->id]) !!}" title="Follow" class="text-muted-foreground hover:text-primary">
                        <i class="bi bi-plus-circle-fill"></i>
                    </a>
                @endif
                
                @if ($like = $thread->likedBy($user))
                    <a href="{!! route('threads.unlike', ['id' => $thread->id]) !!}" title="Unlike" class="text-yellow-500 hover:text-yellow-400">
                        <i class="bi bi-star-fill"></i>
                    </a>
                @else
                    <a href="{!! route('threads.like', ['id' => $thread->id]) !!}" title="Like" class="text-muted-foreground hover:text-yellow-500">
                        <i class="bi bi-star"></i>
                    </a>
                @endif
            @endif
        </div>
    </div>

    @if ($event = $thread->event)
    <div class="mb-2">
        <span class="text-sm text-muted-foreground">Event:</span>
        <a href="{!! route('events.show', ['event' => $event->id]) !!}" class="inline-flex items-center px-2 py-1 bg-card border border-border text-foreground rounded text-sm hover:bg-accent">
            {{ Str::limit($event->name, 30, ' ...') }}
        </a>
    </div>
    @endif

    @unless ($thread->series->isEmpty())
    <div class="mb-2">
        <span class="text-sm text-muted-foreground">Series:</span>
        <div class="inline-flex flex-wrap gap-2">
            @foreach ($thread->series as $series)
            <a href="/threads/series/{{ urlencode($series->slug) }}" class="inline-flex items-center px-2 py-1 bg-card border border-border text-foreground rounded text-sm hover:bg-accent">
                {{ $series->name }}
                <a href="{!! route('series.show', ['series' => $series->slug]) !!}" title="Show this series" class="ml-1 text-primary hover:text-primary/90">
                    <i class="bi bi-link-45deg"></i>
                </a>
            </a>
            @endforeach
        </div>
    </div>
    @endunless

    @unless ($thread->entities->isEmpty())
    <div class="mb-2">
        <span class="text-sm text-muted-foreground">Related:</span>
        <div class="inline-flex flex-wrap gap-2">
            @foreach ($thread->entities as $entity)
            <a href="/threads/relatedto/{{ urlencode($entity->slug) }}" class="inline-flex items-center px-2 py-1 bg-card border border-border text-foreground rounded text-sm hover:bg-accent">
                {{ $entity->name }}
            </a>
            @endforeach
        </div>
    </div>
    @endunless

    @unless ($thread->tags->isEmpty())
    <div class="flex flex-wrap gap-2">
        @foreach ($thread->tags as $tag)
            <x-tag-badge :tag="$tag" context="threads" variant="primary" />
        @endforeach
    </div>
    @endunless
    
    @if ($thread->body)
    <div class="mt-3 text-muted-foreground text-sm">
        {{ Str::limit(strip_tags($thread->body), 200) }}
    </div>
    @endif
</div>
