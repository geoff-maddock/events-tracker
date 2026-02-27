<!-- Thread Row — forum-style (Discourse/phpBB layout) -->
<article class="hover:bg-accent/30 transition-colors" id="thread-card-{{ $thread->id }}">
    <div class="flex items-start gap-3 px-4 py-3">

        {{-- Col 1: Avatar (small, all sizes) --}}
        <div class="flex-shrink-0 mt-0.5">
            @if ($thread->user)
            @include('users.avatar', ['user' => $thread->user, 'size' => 'sm'])
            @else
            <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center">
                <i class="bi bi-person text-sm text-muted-foreground/50"></i>
            </div>
            @endif
        </div>

        {{-- Col 2: Thread info (always visible, flex-1) --}}
        <div class="flex-1 min-w-0">
            {{-- Title --}}
            <h3 class="font-semibold text-foreground leading-snug">
                <a href="{{ route('threads.show', [$thread->id]) }}" class="hover:text-primary transition-colors">
                    @if($thread->is_locked ?? false)
                    <i class="bi bi-lock text-muted-foreground/60 text-xs mr-1"></i>
                    @endif
                    {{ $thread->name }}
                </a>
            </h3>

            {{-- Mobile meta line (hidden on lg+) --}}
            <div class="lg:hidden flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-1 text-xs text-muted-foreground">
                @if ($thread->user)
                <span>{{ $thread->user->name }}</span>
                <span>·</span>
                @endif
                <span>{{ $thread->created_at->diffForHumans() }}</span>
                @if ($thread->posts_count > 0)
                <span>·</span>
                <span><i class="bi bi-chat"></i> {{ $thread->posts_count }}</span>
                @endif
                @if (isset($thread->views))
                <span>·</span>
                <span><i class="bi bi-eye"></i> {{ $thread->views }}</span>
                @endif
            </div>

            {{-- Tags (all sizes, max 3) --}}
            @unless ($thread->tags->isEmpty())
            <div class="flex flex-wrap gap-1 mt-1.5">
                @foreach ($thread->tags->take(3) as $tag)
                    <x-tag-badge :tag="$tag" context="threads" />
                @endforeach
                @if ($thread->tags->count() > 3)
                <span class="text-xs text-muted-foreground/50">+{{ $thread->tags->count() - 3 }}</span>
                @endif
            </div>
            @endunless

            {{-- Related event / series --}}
            @if ($thread->event)
            <div class="flex items-center gap-1 mt-1 text-xs text-muted-foreground">
                <i class="bi bi-calendar-event"></i>
                <a href="{{ route('events.show', [$thread->event->slug]) }}" class="hover:text-primary">{{ Str::limit($thread->event->name, 40) }}</a>
            </div>
            @elseif ($thread->series->isNotEmpty())
            <div class="flex items-center gap-1 mt-1 text-xs text-muted-foreground">
                <i class="bi bi-collection"></i>
                @foreach ($thread->series->take(2) as $s)
                <a href="{{ route('series.show', [$s->slug]) }}" class="hover:text-primary">{{ $s->name }}</a>@if (!$loop->last), @endif
                @endforeach
            </div>
            @endif
        </div>

        {{-- Col 3: Author (lg+) --}}
        <div class="hidden lg:flex flex-col items-center text-center w-28 flex-shrink-0">
            @if ($thread->user)
            <a href="{{ route('users.show', [$thread->user->id]) }}" class="text-xs text-muted-foreground hover:text-primary truncate max-w-full">
                {{ $thread->user->name }}
            </a>
            <span class="text-xs text-muted-foreground/60 mt-0.5">{{ $thread->created_at->diffForHumans() }}</span>
            @endif
        </div>

        {{-- Col 4: Replies / Views (lg+) --}}
        <div class="hidden lg:flex flex-col items-center w-20 flex-shrink-0">
            <span class="text-sm font-medium text-foreground">{{ $thread->posts_count }}</span>
            <span class="text-xs text-muted-foreground">replies</span>
            @if(isset($thread->views))
            <span class="text-xs text-muted-foreground mt-0.5">{{ $thread->views }} views</span>
            @endif
        </div>

        {{-- Col 5: Last activity (lg+) --}}
        <div class="hidden lg:flex flex-col items-end text-right w-32 flex-shrink-0">
            @php
                $lastPostAt = method_exists($thread, 'lastPostAt') ? $thread->lastPostAt : $thread->updated_at;
            @endphp
            <span class="text-xs text-foreground">{{ $lastPostAt->diffForHumans() }}</span>
            @if ($thread->posts_count > 0 && $thread->lastPost?->user)
            <span class="text-xs text-muted-foreground truncate max-w-full mt-0.5">{{ $thread->lastPost->user->name }}</span>
            @endif
        </div>

        {{-- Edit action --}}
        @if ($signedIn && ($thread->user_id == $user->id || $user->hasGroup('super_admin')))
        <div class="flex-shrink-0 self-center">
            <a href="{{ route('threads.edit', [$thread->id]) }}"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="Edit this thread">
                <i class="bi bi-pencil text-sm"></i>
            </a>
        </div>
        @endif

    </div>
</article>
