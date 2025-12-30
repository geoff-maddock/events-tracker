<li id="series-{{ $series->id }}" class="group relative flex flex-col sm:flex-row gap-4 p-4 bg-white dark:bg-dark-card rounded-lg border border-gray-200 dark:border-dark-border hover:border-primary dark:hover:border-primary transition-colors mb-4 shadow-sm dark:shadow-none">
    <!-- Thumbnail -->
    <div class="flex-shrink-0">
        @if ($primary = $series->getPrimaryPhoto())
        <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}" 
            data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='/series/{{ $series->id }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venue ? $series->venue->name : '' }}</a>"
            data-lightbox="{{ $primary->path }}"
            class="block w-24 h-24 rounded overflow-hidden border border-gray-200 dark:border-dark-border group-hover:border-primary transition-colors">
            <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $series->name }}" class="w-full h-full object-cover">
        </a>
        @else
        <a href="/images/event-placeholder.png" 
            data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='/series/{{ $series->id }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venue ? $series->venue->name : '' }}</a>"
            data-lightbox="/images/event-placeholder.png"
            class="block w-24 h-24 rounded overflow-hidden border border-gray-200 dark:border-dark-border group-hover:border-primary transition-colors">
            <img src="/images/event-placeholder.png" alt="{{ $series->name }}" class="w-full h-full object-cover">
        </a>
        @endif
    </div>

    <!-- Content -->
    <div class="flex-grow min-w-0">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-2 mb-1">
            <div class="flex flex-col">
                <!-- Occurrence Type -->
                <span class="text-xs font-bold text-primary uppercase tracking-wider mb-1">
                    {{ $series->occurrenceType->name }} {{ $series->occurrence_repeat }}
                </span>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                @if ($signedIn && ($series->ownedBy($user) || $user->hasGroup('super_admin')))
                <a href="{!! route('series.edit', ['series' => $series->slug]) !!}"
                    class="text-gray-400 hover:text-primary transition-colors"
                    title="Edit this series">
                    <i class="bi bi-pencil-fill"></i>
                </a>

                <a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}"
                    class="text-gray-400 hover:text-primary transition-colors"
                    title="Create next occurrence">
                    <i class="bi bi-calendar-plus"></i>
                </a>
                @endif

                @if ($signedIn)
                    @if ($follow = $series->followedBy($user))
                    <a href="{!! route('series.unfollow', ['id' => $series->id]) !!}" 
                       class="text-primary hover:text-red-500 transition-colors"
                       title="Unfollow">
                        <i class="bi bi-dash-circle-fill"></i>
                    </a>
                    @else
                    <a href="{!! route('series.follow', ['id' => $series->id]) !!}" 
                       class="text-gray-400 hover:text-primary transition-colors"
                       title="Follow">
                        <i class="bi bi-plus-circle-fill"></i>
                    </a>
                    @endif
                @endif
            </div>
        </div>

        <!-- Title -->
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
            <a href='/series/{{ $series->slug }}' class="hover:text-primary transition-colors">
                {{ $series->name }}
            </a>
        </h3>

        <!-- Venue -->
        @if ($series->venue)
        <div class="text-gray-600 dark:text-gray-300 mb-2">
            <span class="text-gray-400 dark:text-gray-500">@</span> 
            <a href='/entities/{{ $series->venue->slug }}' class="hover:text-primary transition-colors">
                {{ $series->venue->name }}
            </a>
        </div>
        @endif

        <!-- Time -->
        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400 mb-2">
            @if ($series->start_at)
            <span class="flex items-center gap-1">
                <i class="bi bi-clock"></i>
                {{ $series->start_at->format('g:i A') }}
            </span>
            @endif
        </div>

        <!-- Tags -->
        @unless ($series->tags->isEmpty())
        <div class="flex flex-wrap gap-1">
            @foreach ($series->tags->take(3) as $tag)
            <a href="/series/tag/{{ $tag->slug }}" class="badge-tw badge-secondary-tw text-xs hover:bg-gray-200 dark:hover:bg-dark-border">
                {{ $tag->name }}
            </a>
            @endforeach
            @if ($series->tags->count() > 3)
            <span class="text-xs text-gray-500 self-center">+{{ $series->tags->count() - 3 }}</span>
            @endif
        </div>
        @endunless
    </div>
</li>