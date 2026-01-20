<tr class="hover:bg-accent/50 transition-colors">
    <td class="px-4 py-3 w-full md:w-auto">
        <div class="mb-2">
            {!! link_to_route('threads.show', $thread->name, [$thread->id], ['class' => 'text-primary hover:underline font-medium text-base md:text-lg']) !!}
        </div>
        
        <div class="flex items-center gap-2 flex-wrap mb-2">
            @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
                <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}" 
                   title="Edit this thread." 
                   class="inline-flex items-center px-2 py-1 text-xs md:text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                    <i class="bi bi-pencil-fill"></i>
                </a>
                {!! link_form_bootstrap_icon('bi bi-trash-fill text-destructive', $thread, 'DELETE', 'Delete the [thread]', NULL, 'py-0 my-0', 'confirm') !!}
                @if (!$thread->is_locked)
                    <a href="{!! route('threads.lock', ['id' => $thread->id]) !!}" 
                       title="Lock this thread."
                       class="inline-flex items-center px-2 py-1 text-xs md:text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                        <i class="bi bi-unlock-fill"></i>
                    </a>
                @else
                    <a href="{!! route('threads.unlock', ['id' => $thread->id]) !!}" 
                       title="Thread is locked. Click to unlock."
                       class="inline-flex items-center px-2 py-1 text-xs md:text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                        <i class="bi bi-lock-fill"></i>
                    </a>
                @endif
            @endif
            @if ($signedIn)
                @if ($follow = $thread->followedBy($user))
                    <a href="{!! route('threads.unfollow', ['id' => $thread->id]) !!}" 
                       title="Click to unfollow"
                       class="inline-flex items-center px-2 py-1 text-xs md:text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                        <i class="bi bi-dash-circle-fill text-warning"></i>
                    </a>
                @else
                    <a href="{!! route('threads.follow', ['id' => $thread->id]) !!}" 
                       title="Click to follow"
                       class="inline-flex items-center px-2 py-1 text-xs md:text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                        <i class="bi bi-plus-circle-fill text-info"></i>
                    </a>
                @endif
                @if ($like = $thread->likedBy($user))
                    <a href="{!! route('threads.unlike', ['id' => $thread->id]) !!}" 
                       title="Click to unlike"
                       class="inline-flex items-center px-2 py-1 text-xs md:text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                        <i class="bi bi-star-fill"></i>
                    </a>
                @else
                    <a href="{!! route('threads.like', ['id' => $thread->id]) !!}" 
                       title="Click to like"
                       class="inline-flex items-center px-2 py-1 text-xs md:text-sm bg-card border border-border rounded hover:bg-accent transition-colors">
                        <i class="bi bi-star text-info"></i>
                    </a>
                @endif
            @endif
        </div>

        @if ($event = $thread->event)
            <div class="mb-2">
                <span class="text-xs md:text-sm text-muted-foreground mr-2">Event:</span>
                <a href="{!! route('events.show', ['event' => $event->id]) !!}" 
                   class="badge-tw badge-primary-tw text-xs">
                    {{ Str::limit($event->name,30,' ...') }}
                </a>
            </div>
        @endif

        @unless ($thread->series->isEmpty())
            <div class="mb-2">
                <span class="text-xs md:text-sm text-muted-foreground mr-2">Series:</span>
                <div class="inline-flex flex-wrap gap-1">
                    @foreach ($thread->series as $series)
                        <a href="/threads/series/{{ urlencode($series->slug) }}" 
                           class="badge-tw badge-primary-tw text-xs">
                            {{ $series->name }}
                            <a href="{!! route('series.show', ['series' => $series->slug]) !!}" 
                               title="Show this series." 
                               class="ml-1">
                                <i class="bi bi-link-45deg"></i>
                            </a>
                        </a>
                    @endforeach
                </div>
            </div>
        @endunless

        @unless ($thread->entities->isEmpty())
            <div class="mb-2">
                <span class="text-xs md:text-sm text-muted-foreground mr-2">Related:</span>
                <div class="inline-flex flex-wrap gap-1">
                    @foreach ($thread->entities as $entity)
                        <a href="/threads/related-to/{{ urlencode($entity->slug) }}" 
                           class="badge-tw badge-primary-tw text-xs">
                            {{ $entity->name }}
                            <a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" 
                               title="Show this entity." 
                               class="ml-1">
                                <i class="bi bi-link-45deg"></i>
                            </a>
                        </a>
                    @endforeach
                </div>
            </div>
        @endunless

        @unless ($thread->tags->isEmpty())
            <div class="mb-2">
                <span class="text-xs md:text-sm text-muted-foreground mr-2">Tags:</span>
                <div class="inline-flex flex-wrap gap-1">
                    @foreach ($thread->tags as $tag)
                        <x-tag-badge :tag="$tag" context="threads" />
                    @endforeach
                </div>
            </div>
        @endunless

        <!-- Mobile-only stats -->
        <div class="flex items-center gap-4 mt-3 text-xs text-muted-foreground md:hidden">
            <span title="Posts"><i class="bi bi-chat-dots mr-1"></i>{{ $thread->postCount }}</span>
            <span title="Views"><i class="bi bi-eye mr-1"></i>{{ $thread->views }}</span>
            <span title="Likes"><i class="bi bi-star mr-1"></i>{{ $thread->likes }}</span>
            <span class="ml-auto">{{ $thread->lastPostAt->diffForHumans() }}</span>
        </div>

    </td>
    <td class="px-3 py-3 hidden md:table-cell text-center w-24 lg:w-32">
        <span class="text-xs lg:text-sm">{{ $thread->thread_category ?? 'General'}}</span>
    </td>
    <td class="px-3 py-3 w-auto md:w-32 lg:w-40">
        @if (isset($thread->user))
            <div class="flex items-center gap-2">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full overflow-hidden bg-muted">
                        @if ($photo = $thread->user->getPrimaryPhoto())
                        <img src="{!! str_replace(' ','%20', Storage::disk('external')->url($photo->getStoragePath()) ) !!}" 
                             alt="{{ $thread->user->name}}" 
                             class="w-full h-full object-cover" 
                             title="{{ $thread->user->name }}">
                        @else
                        <img src="/images/avatar-placeholder-generic.jpg" 
                             alt="{{ $thread->user->name}}" 
                             class="w-full h-full object-cover" 
                             title="{{ $thread->user->name }}">
                        @endif
                    </div>
                </div>
                <div class="hidden lg:block min-w-0 flex-1">
                    <div class="text-sm truncate">
                        {!! link_to_route('users.show', $thread->user->name, [$thread->user->id], ['class' => 'text-primary hover:underline']) !!}
                    </div>
                </div>
            </div>
        @else
            <span class="text-xs text-muted-foreground italic">User deleted</span>
        @endif
    </td>
    <td class="px-3 py-3 text-center hidden md:table-cell w-16 lg:w-20">
        <span class="text-sm">{{ $thread->postCount }}</span>
    </td>
    <td class="px-3 py-3 text-center hidden md:table-cell w-16 lg:w-20">
        <span class="text-sm">{{ $thread->views }}</span>
    </td>
    <td class="px-3 py-3 text-center hidden md:table-cell w-16 lg:w-20">
        <span class="text-sm">{{ $thread->likes }}</span>
    </td>
    <td class="px-3 py-3 hidden lg:table-cell w-32 xl:w-40">
        <span class="text-xs xl:text-sm text-muted-foreground">{{ $thread->lastPostAt->diffForHumans() }}</span>
    </td>
</tr>
<tr>
    <td colspan="7" class="px-4 py-4 bg-accent/20 border-b border-border">
        <div class="prose prose-sm max-w-none dark:prose-invert">
            @if (isset($thread->user) && $thread->user->can('trust_thread'))
                {!! $thread->body !!}
            @else
                {{ $thread->body }}
            @endcan
        </div>
    </td>
</tr>
