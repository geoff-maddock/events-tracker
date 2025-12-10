<!-- Thread Card Component -->
<article class="card-tw hover:border-primary/30 transition-all" id="thread-card-{{ $thread->id }}">
    <div class="p-4">
        <div class="flex items-start gap-4">
            <!-- Author Avatar -->
            @if ($thread->user)
            <div class="flex-shrink-0">
                @if ($thread->user->profile && $thread->user->profile->avatar)
                <img src="{{ Storage::disk('external')->url($thread->user->profile->getStorageThumbnail()) }}" 
                    alt="{{ $thread->user->name }}" 
                    class="w-12 h-12 rounded-full object-cover">
                @else
                <div class="w-12 h-12 rounded-full bg-dark-card flex items-center justify-center">
                    <i class="bi bi-person text-xl text-gray-500"></i>
                </div>
                @endif
            </div>
            @endif

            <!-- Thread Content -->
            <div class="flex-1 min-w-0">
                <!-- Thread Title -->
                <h3 class="text-lg font-semibold text-white hover:text-primary transition-colors mb-2">
                    <a href="{{ route('threads.show', [$thread->id]) }}">{{ $thread->name }}</a>
                </h3>

                <!-- Thread Meta -->
                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-400 mb-3">
                    <!-- Author -->
                    @if ($thread->user)
                    <div class="flex items-center gap-1">
                        <i class="bi bi-person"></i>
                        <a href="{{ route('users.show', [$thread->user->id]) }}" class="hover:text-primary">{{ $thread->user->name }}</a>
                    </div>
                    @endif

                    <!-- Date -->
                    <div class="flex items-center gap-1">
                        <i class="bi bi-clock"></i>
                        <span>{{ $thread->created_at->diffForHumans() }}</span>
                    </div>

                    <!-- Views Count -->
                    @if (isset($thread->views))
                    <div class="flex items-center gap-1">
                        <i class="bi bi-eye"></i>
                        <span>{{ $thread->views }} views</span>
                    </div>
                    @endif

                    <!-- Likes Count -->
                    @if (isset($thread->likes) && $thread->likes > 0)
                    <div class="flex items-center gap-1">
                        <i class="bi bi-heart"></i>
                        <span>{{ $thread->likes }} likes</span>
                    </div>
                    @endif

                    <!-- Comments Count -->
                    @if ($thread->posts->count() > 0)
                    <div class="flex items-center gap-1">
                        <i class="bi bi-chat"></i>
                        <span>{{ $thread->posts->count() }} {{ $thread->posts->count() == 1 ? 'comment' : 'comments' }}</span>
                    </div>
                    @endif
                </div>

                <!-- Thread Body Preview -->
                @if ($thread->body)
                <div class="text-gray-300 text-sm mb-3 line-clamp-3">
                    {!! Str::limit(strip_tags($thread->body), 200) !!}
                </div>
                @endif

                <!-- Tags -->
                @unless ($thread->tags->isEmpty())
                <div class="flex flex-wrap gap-1 mb-3">
                    @foreach ($thread->tags->take(5) as $tag)
                    <a href="/tags/{{ $tag->slug }}" class="badge-tw badge-secondary-tw text-xs hover:bg-dark-border">
                        {{ $tag->name }}
                    </a>
                    @endforeach
                    @if ($thread->tags->count() > 5)
                    <span class="text-xs text-gray-500">+{{ $thread->tags->count() - 5 }} more</span>
                    @endif
                </div>
                @endunless

                <!-- Related Series/Event -->
                @if ($thread->event)
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="bi bi-calendar-event"></i>
                    <span>Related to</span>
                    <a href="{{ route('events.show', [$thread->event->slug]) }}" class="text-primary hover:text-primary-hover">
                        {{ $thread->event->name }}
                    </a>
                </div>
                @elseif ($thread->series)
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <i class="bi bi-collection"></i>
                    <span>Related to</span>
                    <a href="{{ route('series.show', [$thread->series->slug]) }}" class="text-primary hover:text-primary-hover">
                        {{ $thread->series->name }}
                    </a>
                </div>
                @endif
            </div>

            <!-- Actions Menu -->
            @if ($signedIn && ($thread->user_id == $user->id || $user->hasGroup('super_admin')))
            <div class="flex-shrink-0">
                <div class="flex items-center gap-2">
                    <a href="{{ route('threads.edit', [$thread->id]) }}" 
                        class="text-gray-400 hover:text-primary transition-colors"
                        title="Edit this thread">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</article>
