{{-- Original post card --}}
<div class="border border-border rounded-lg overflow-hidden">

    {{-- Thread title bar --}}
    <div class="bg-muted/60 border-b border-border px-4 py-3 flex items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold text-foreground text-lg leading-snug">
                @if($thread->is_locked)
                <i class="bi bi-lock text-muted-foreground/60 text-base mr-1"></i>
                @endif
                {{ $thread->name }}
            </h2>
            {{-- Metadata badges --}}
            <div class="flex flex-wrap gap-1 mt-2">
                @if ($event = $thread->event)
                <a href="{!! route('events.show', ['event' => $event->id]) !!}" class="badge-tw badge-primary-tw text-xs">
                    <i class="bi bi-calendar-event mr-1"></i>{{ Str::limit($event->name, 30, ' ...') }}
                </a>
                @endif
                @unless ($thread->series->isEmpty())
                    @foreach ($thread->series as $series)
                    <a href="{{ route('series.show', ['series' => $series->slug]) }}" class="badge-tw badge-secondary-tw text-xs">
                        <i class="bi bi-collection mr-1"></i>{{ $series->name }}
                    </a>
                    @endforeach
                @endunless
                @unless ($thread->entities->isEmpty())
                    @foreach ($thread->entities as $entity)
                    <x-entity-badge :entity="$entity" context="threads" variant="primary" />
                    @endforeach
                @endunless
                @unless ($thread->tags->isEmpty())
                    @foreach ($thread->tags as $tag)
                    <x-tag-badge :tag="$tag" context="threads" />
                    @endforeach
                @endunless
            </div>
        </div>
        {{-- Actions --}}
        @if ($signedIn)
        <div class="flex items-center gap-1 flex-shrink-0">
            @if (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin'))
            <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}"
               title="Edit"
               class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                <i class="bi bi-pencil-fill"></i>
            </a>
            {!! link_form_bootstrap_icon('bi bi-trash-fill text-destructive', $thread, 'DELETE', 'Delete', NULL, 'py-0 my-0', 'confirm') !!}
            @if (!$thread->is_locked)
            <a href="{!! route('threads.lock', ['id' => $thread->id]) !!}"
               title="Lock"
               class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                <i class="bi bi-unlock-fill"></i>
            </a>
            @else
            <a href="{!! route('threads.unlock', ['id' => $thread->id]) !!}"
               title="Unlock"
               class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                <i class="bi bi-lock-fill"></i>
            </a>
            @endif
            @endif
            @if ($follow = ($threadFollow ?? $thread->followedBy($user)))
            <a href="{!! route('threads.unfollow', ['id' => $thread->id]) !!}"
               title="Unfollow"
               class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                <i class="bi bi-dash-circle-fill text-warning"></i>
            </a>
            @else
            <a href="{!! route('threads.follow', ['id' => $thread->id]) !!}"
               title="Follow"
               class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                <i class="bi bi-plus-circle-fill text-info"></i>
            </a>
            @endif
            @if ($like = ($threadLike ?? $thread->likedBy($user)))
            <a href="{!! route('threads.unlike', ['id' => $thread->id]) !!}"
               title="Unlike"
               class="inline-flex items-center gap-1 px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                <i class="bi bi-star-fill text-warning"></i>
                @if($thread->likes > 0)<span class="text-xs">{{ $thread->likes }}</span>@endif
            </a>
            @else
            <a href="{!! route('threads.like', ['id' => $thread->id]) !!}"
               title="Like"
               class="inline-flex items-center gap-1 px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                <i class="bi bi-star"></i>
                @if($thread->likes > 0)<span class="text-xs">{{ $thread->likes }}</span>@endif
            </a>
            @endif
        </div>
        @endif
    </div>

    {{-- Post body with author sidebar --}}
    <div class="flex">

        {{-- Author sidebar (sm+) --}}
        <div class="hidden sm:flex flex-col items-center gap-2 py-5 px-3 bg-muted/30 border-r border-border w-28 flex-shrink-0 text-center">
            @if (isset($thread->user))
            @include('users.avatar', ['user' => $thread->user, 'size' => 'xl'])
            <a href="{{ route('users.show', [$thread->user]) }}"
               class="text-xs font-medium text-foreground hover:text-primary leading-tight break-all">
                {{ $thread->user->name }}
            </a>
            <span class="text-xs text-muted-foreground">{{ $thread->created_at->format('M j, Y') }}</span>
            <div class="flex items-center gap-1 text-xs text-muted-foreground mt-1">
                <i class="bi bi-eye"></i><span>{{ $thread->views }}</span>
            </div>
            @else
            <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center">
                <i class="bi bi-person text-2xl text-muted-foreground/40"></i>
            </div>
            <span class="text-xs text-muted-foreground italic">Deleted</span>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0 p-4 sm:p-5">

            {{-- Mobile author bar --}}
            <div class="sm:hidden flex items-center gap-2 mb-4 pb-3 border-b border-border">
                @if (isset($thread->user))
                @include('users.avatar', ['user' => $thread->user, 'size' => 'sm'])
                <div class="min-w-0">
                    <a href="{{ route('users.show', [$thread->user]) }}" class="text-sm font-medium hover:text-primary">{{ $thread->user->name }}</a>
                    <div class="text-xs text-muted-foreground">{{ $thread->created_at->diffForHumans() }}</div>
                </div>
                @endif
            </div>

            {{-- Body --}}
            <div class="prose prose-sm max-w-none dark:prose-invert">
                @if (isset($thread->user) && $thread->user->can('trust_thread'))
                    {!! $thread->body !!}
                @else
                    {{ $thread->body }}
                @endcan
            </div>
        </div>
    </div>
</div>
