@if (isset($threads) && count($threads) > 0)

<div class="overflow-x-auto">
    <table class="w-full">
        <thead class="bg-muted border-b border-border">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-foreground">Thread</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-foreground hidden lg:table-cell">Category</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-foreground hidden sm:table-cell">User</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-foreground hidden md:table-cell">Posts</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-foreground hidden md:table-cell">Views</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-foreground">Last Post</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @foreach ($threads as $thread)
                <tr class="hover:bg-accent/50 transition-colors">
                    <td class="px-4 py-3">
                        <div>
                            <a href="{{ route('threads.show', [$thread->id]) }}" id="thread-{{ $thread->id }}" title="Topic: {{ $thread->name }}" class="font-medium text-foreground hover:text-primary transition-colors">
                                {{ $thread->name }}
                            </a>

                            @if ($event = $thread->event)
                                <a href="{{ route('events.show', ['event' => $event->id]) }}" title="Show event" class="text-primary ml-1">
                                    <i class="bi bi-calendar text-sm"></i>
                                </a>
                            @endif

                            @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
                                <a href="{{ route('threads.edit', ['thread' => $thread->id]) }}" title="Edit this thread" class="text-muted-foreground hover:text-foreground ml-1">
                                    <i class="bi bi-pencil-fill text-sm"></i>
                                </a>
                            @endif
                        </div>

                        <div class="hidden md:block mt-1">
                            @if ($event = $thread->event)
                                <span class="text-xs text-muted-foreground">Event:</span>
                                <a href="{{ route('events.show', ['event' => $event->id]) }}" class="badge-tw badge-secondary-tw text-xs">
                                    {{ Str::limit($event->name, 30, ' ...') }}
                                </a>
                            @endif

                            @unless ($thread->series->isEmpty())
                                <span class="text-xs text-muted-foreground ml-2">Series:</span>
                                @foreach ($thread->series as $series)
                                    <a href="/threads/series/{{ urlencode($series->slug) }}" class="badge-tw badge-secondary-tw text-xs">
                                        {{ $series->name }}
                                    </a>
                                @endforeach
                            @endunless

                            @unless ($thread->entities->isEmpty())
                                <span class="text-xs text-muted-foreground ml-2">Related:</span>
                                @foreach ($thread->entities as $entity)
                                    <x-entity-badge :entity="$entity" context="threads" variant="primary" />
                                @endforeach
                            @endunless

                            @unless ($thread->tags->isEmpty())
                                <span class="text-xs text-muted-foreground ml-2">Tags:</span>
                                @foreach ($thread->tags as $tag)
                                    <x-tag-badge :tag="$tag" context="threads" />
                                @endforeach
                            @endunless
                        </div>
                    </td>

                    <td class="px-4 py-3 hidden lg:table-cell">
                        @if (isset($thread->threadCategory))
                            <a href="/threads/category/{{ urlencode($thread->threadCategory->name) }}" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                                {{ $thread->threadCategory->name }}
                            </a>
                        @else
                            <span class="text-sm text-muted-foreground">General</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 hidden sm:table-cell">
                        @if (isset($thread->user))
                            <div class="flex items-center gap-2">
                                @include('users.avatar', ['user' => $thread->user])
                                <span class="hidden xl:inline">
                                    <a href="{{ route('users.show', [$thread->user->id]) }}" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                                        {{ $thread->user->name }}
                                    </a>
                                </span>
                            </div>
                        @else
                            <span class="text-sm text-muted-foreground">User deleted</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center hidden md:table-cell">
                        <span class="text-sm text-muted-foreground">{{ $thread->postCount }}</span>
                    </td>

                    <td class="px-4 py-3 text-center hidden md:table-cell">
                        <span class="text-sm text-muted-foreground">{{ $thread->views }}</span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-sm text-muted-foreground">{{ $thread->lastPostAt->diffForHumans() }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@else
    <div class="text-center py-8 text-muted-foreground italic">
        No threads listed
    </div>
@endif
