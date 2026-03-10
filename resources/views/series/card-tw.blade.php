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
        <div class="absolute top-2 right-2">
            @if ($signedIn)
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
            @else
                <a href="{!! route('login') !!}"
                    class="p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                    title="Sign in to follow">
                    <i class="bi bi-plus-circle text-muted-foreground hover:text-primary text-lg"></i>
                </a>
            @endif
        </div>

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

            <!-- Next Edition Bar -->
            @php $nextEvent = $series->nextEvent(); @endphp
            @if ($nextEvent)
            <div class="rounded border border-primary/30 bg-primary/5 px-3 py-2 flex items-start gap-2">
                <i class="bi bi-calendar-event text-primary mt-0.5 flex-shrink-0"></i>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-primary uppercase tracking-wide mb-0.5">Next Edition</div>
                    <div class="flex flex-wrap items-center gap-x-1 text-sm">
                        <span class="font-medium text-foreground">{{ $nextEvent->start_at->format('D, M j, Y') }}</span>
                        @if ($nextEvent->start_at->format('H:i') !== '00:00')
                        <span class="text-muted-foreground">&middot; {{ $nextEvent->start_at->format('g:i A') }}</span>
                        @endif
                    </div>
                    <a href="{{ route('events.show', [$nextEvent->slug]) }}" class="text-primary hover:underline text-sm truncate block">{{ $nextEvent->name }}</a>
                </div>
            </div>
            @elseif ($series->occurrenceType->name !== 'No Schedule' && $series->cancelled_at === null && ($nextDate = $series->cycleFromFoundedAt()))
            <div class="rounded border border-border bg-muted/50 px-3 py-2 flex items-start gap-2">
                <i class="bi bi-calendar3 text-muted-foreground mt-0.5 flex-shrink-0"></i>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-0.5">Next Edition</div>
                    <div class="flex flex-wrap items-center gap-x-1 text-sm">
                        <span class="font-medium text-foreground">{{ $nextDate->format('D, M j, Y') }}</span>
                        @if ($series->start_at && $series->start_at->format('H:i') !== '00:00')
                        <span class="text-muted-foreground">&middot; {{ $series->start_at->format('g:i A') }}</span>
                        @endif
                    </div>
                    <span class="badge-tw badge-secondary-tw text-xs mt-0.5 inline-block">Not yet created as event</span>
                </div>
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
                <x-entity-badge :entity="$entity" context="series" />
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
                <x-tag-badge :tag="$tag" context="series" />
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
