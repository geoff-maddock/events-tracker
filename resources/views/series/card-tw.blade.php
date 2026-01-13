<!-- Series Card Component -->
<article class="event-card-tw group" id="series-card-{{ $series->id }}">
    <!-- Series Image -->
    <div class="relative overflow-hidden">
        @if ($primary = $series->getPrimaryPhoto())
        <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
            data-title="{{ $series->occurrenceType->name }} {{ $series->occurrence_repeat }} - {{ $series->name }} @ {{ $series->venue ? $series->venue->name : '' }}"
            data-lightbox="{{ $primary->path }}">
            <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}"
                alt="{{ $series->name }}"
                class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300">
        </a>
        @else
        <a href="/images/event-placeholder.png"
            data-lightbox="series-{{ $series->id }}">
            <div class="w-full aspect-square bg-card flex items-center justify-center">
                <i class="bi bi-collection text-4xl text-muted-foreground/60"></i>
            </div>
        </a>
        @endif

        <!-- Follow/Unfollow Button -->
        @if ($signedIn)
        <div class="absolute top-2 right-2">
            @if ($follow = $series->followedBy($user))
            <a href="{!! route('series.unfollow', ['id' => $series->id]) !!}"
                data-target="#series-card-{{ $series->id }}"
                class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                title="Click to unfollow">
                <i class="bi bi-dash-circle-fill text-primary text-lg"></i>
            </a>
            @else
            <a href="{!! route('series.follow', ['id' => $series->id]) !!}" 
                data-target="#series-card-{{ $series->id }}" 
                class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                title="Click to follow">
                <i class="bi bi-plus-circle text-muted-foreground hover:text-primary text-lg"></i>
            </a>
            @endif
        </div>
        @endif

        <!-- Visibility Badge -->
        @if ($series->visibility->name !== 'Public')
        <div class="absolute top-2 left-2">
            <span class="badge-tw badge-warning-tw">{{ $series->visibility->name }}</span>
        </div>
        @endif

        <!-- Cancelled Badge -->
        @if ($series->cancelled_at != NULL)
        <div class="absolute top-2 left-2">
            <span class="badge-tw badge-destructive-tw">Cancelled</span>
        </div>
        @endif
    </div>

    <!-- Card Content -->
    <div class="event-card-content-tw">
        <!-- Occurrence Type Badge -->
        <div class="mb-2">
            <span class="badge-tw badge-accent-tw text-xs">
                {{ $series->occurrenceType->name }} {{ $series->occurrence_repeat }}
            </span>
        </div>

        <!-- Series Title -->
        <h3 class="event-card-title-tw mb-2 line-clamp-2">
            <a href="{{ route('series.show', [$series->slug]) }}">{{ $series->name }}</a>
        </h3>

        <!-- Short Description -->
        @if ($series->short)
        <p class="text-sm text-muted-foreground mb-3 line-clamp-2">{{ $series->short }}</p>
        @endif

        <!-- Series Meta Info -->
        <div class="space-y-2 text-sm text-muted-foreground mb-4">
            <!-- Next Event Date -->
            @if ($series->occurrenceType->name !== 'No Schedule')
            <div class="flex items-center gap-2">
                <i class="bi bi-calendar3"></i>
                <span>Next: {{ $series->nextEvent() ? $series->nextEvent()->start_at->format('l F jS Y') : $series->cycleFromFoundedAt()->format('l F jS Y') }}</span>
            </div>
            @endif

            <!-- Venue -->
            @if ($venue = $series->venue)
            <div class="flex items-center gap-2">
                <i class="bi bi-geo-alt"></i>
                <a href="/entities/{{ urlencode($series->venue->slug) }}" class="hover:text-primary">{{ $series->venue->name }}</a>
                @if ($series->venue->getPrimaryLocationMap())
                <a href="{{ $series->venue->getPrimaryLocationMap() }}" target="_blank" rel="noopener" title="{{ $series->venue->getPrimaryLocationAddress() }}" class="text-primary hover:text-primary/90">
                    <i class="bi bi-box-arrow-up-right text-xs"></i>
                </a>
                @endif
            </div>
            @endif

            <!-- Next Event Link -->
            @if ($event = $series->nextEvent())
            <div class="flex items-center gap-2">
                <i class="bi bi-arrow-right-circle"></i>
                <a href="{{ route('events.show', [$event->slug]) }}" class="hover:text-primary">{{ $event->name }}</a>
            </div>
            @endif

            <!-- Cancelled Date -->
            @if ($series->cancelled_at != NULL)
            <div class="flex items-center gap-2">
                <i class="bi bi-x-circle"></i>
                <span>Cancelled {{ $series->cancelled_at ? $series->cancelled_at->format('l F jS Y') : 'unknown' }}</span>
            </div>
            @endif
        </div>

        <!-- Related Entities Tags -->
        @unless ($series->entities->isEmpty())
        <div class="flex flex-wrap gap-1 mb-2">
            @foreach ($series->entities->take(3) as $entity)
            <a href="/entities/{{ $entity->slug }}" class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
                {{ $entity->name }}
                <i class="bi bi-box-arrow-up-right ml-1 text-xs"></i>
            </a>
            @endforeach
            @if ($series->entities->count() > 3)
            <span class="badge-tw badge-secondary-tw text-xs">+{{ $series->entities->count() - 3 }} more</span>
            @endif
        </div>
        @endunless

        <!-- Tags -->
        @unless ($series->tags->isEmpty())
        <div class="flex flex-wrap gap-1 mt-auto pt-2">
            @foreach ($series->tags->take(5) as $tag)
            <a href="/tags/{{ $tag->slug }}" class="badge-tw badge-secondary-tw text-xs hover:bg-accent">
                {{ $tag->name }}
            </a>
            @endforeach
            @if ($series->tags->count() > 5)
            <span class="text-xs text-muted-foreground/50">+{{ $series->tags->count() - 5 }} more</span>
            @endif
        </div>
        @endunless
    </div>

    <!-- Card Footer Actions -->
    <div class="px-4 py-3 border-t border-border flex items-center justify-between">
        <div class="flex items-center gap-2">
            <!-- Edit Button -->
            @if ($signedIn && ($series->ownedBy($user) || $user->hasGroup('super_admin')))
            <a href="{{ route('series.edit', ['series' => $series->slug]) }}"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="Edit this series">
                <i class="bi bi-pencil"></i>
            </a>
            @endif

            <!-- Create Occurrence Button -->
            @if ($signedIn && ($series->ownedBy($user) || $user->hasGroup('super_admin')))
            <a href="{{ route('series.createOccurrence', ['id' => $series->id]) }}"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="Create next occurrence">
                <i class="bi bi-calendar-plus"></i>
            </a>
            @endif
        </div>
    </div>
</article>
