<li id="series-{{ $series->id }}" class="group relative flex flex-col sm:flex-row gap-3 sm:gap-4 p-3 sm:p-4 bg-card rounded-lg border border-border hover:border-primary transition-colors mb-3 sm:mb-4 shadow-sm min-w-0">
    <!-- Thumbnail -->
    <div class="flex-shrink-0">
        @if ($primary = $series->getPrimaryPhoto())
        <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
            data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='/series/{{ $series->id }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venue ? $series->venue->name : '' }}</a>"
            data-lightbox="{{ $primary->path }}"
            class="block aspect-square w-20 sm:w-24 md:w-28 rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors">
            <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $series->name }}" class="w-full h-full object-cover">
        </a>
        @else
        <a href="/images/event-placeholder.png"
            data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='/series/{{ $series->id }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venue ? $series->venue->name : '' }}</a>"
            data-lightbox="/images/event-placeholder.png"
            class="block aspect-square w-20 sm:w-24 md:w-28 rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors bg-card flex items-center justify-center">
            <i class="bi bi-collection text-4xl text-muted-foreground/40"></i>
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
                    class="text-muted-foreground hover:text-primary transition-colors"
                    title="Edit this series">
                    <i class="bi bi-pencil-fill"></i>
                </a>

                <a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}"
                    class="text-muted-foreground hover:text-primary transition-colors"
                    title="Create next occurrence">
                    <i class="bi bi-calendar-plus"></i>
                </a>
                @endif

                @if ($signedIn)
                    @if ($follow = $series->followedBy($user))
                    <a href="{!! route('series.unfollow', ['id' => $series->id]) !!}"
                       class="text-primary hover:text-destructive transition-colors"
                       title="Unfollow">
                        <i class="bi bi-dash-circle-fill"></i>
                    </a>
                    @else
                    <a href="{!! route('series.follow', ['id' => $series->id]) !!}"
                       class="text-muted-foreground hover:text-primary transition-colors"
                       title="Follow">
                        <i class="bi bi-plus-circle-fill"></i>
                    </a>
                    @endif
                @endif
            </div>
        </div>

        <!-- Title -->
        <h3 class="text-lg sm:text-xl font-bold text-foreground mb-1 break-words">
            <a href='/series/{{ $series->slug }}' class="hover:text-primary transition-colors">
                {{ $series->name }}
            </a>
        </h3>

        <!-- Venue -->
        @if ($series->venue)
        <div class="text-sm sm:text-base text-muted-foreground mb-2 break-words">
            <span class="text-muted-foreground/70">@</span>
            <a href='/entities/{{ $series->venue->slug }}' class="hover:text-primary transition-colors">
                {{ $series->venue->name }}
            </a>
        </div>
        @endif

        <!-- Time -->
        <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground mb-2">
            @if ($series->start_at)
            <span class="flex items-center gap-1">
                <i class="bi bi-clock"></i>
                {{ $series->start_at->format('g:i A') }}
            </span>
            @endif
        </div>

        <!-- Entities -->
        @unless ($series->entities->isEmpty())
        <div class="flex flex-wrap gap-1 mb-2">
            @foreach ($series->entities->take(4) as $entity)
                <x-entity-badge :entity="$entity" context="series" />
            @endforeach
            @if ($series->entities->count() > 4)
            <span class="text-xs text-muted-foreground self-center">+{{ $series->entities->count() - 4 }}</span>
            @endif
        </div>
        @endunless

        <!-- Tags -->
        @unless ($series->tags->isEmpty())
        <div class="flex flex-wrap gap-1">
            @foreach ($series->tags->take(3) as $tag)
                <x-tag-badge :tag="$tag" context="series" />
            @endforeach
            @if ($series->tags->count() > 3)
            <span class="text-xs text-muted-foreground self-center">+{{ $series->tags->count() - 3 }}</span>
            @endif
        </div>
        @endunless
    </div>
</li>