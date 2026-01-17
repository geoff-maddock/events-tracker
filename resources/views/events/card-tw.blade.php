<!-- Event Card Component -->
<article class="event-card-tw group {{ $event->visibility->name === 'Cancelled' ? 'opacity-50' : '' }}" id="event-card-{{ $event->id }}">
    <!-- Event Image -->
    <div class="relative overflow-hidden">
        @if ($primary = $event->getPrimaryPhoto())
        <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
            data-title="{!! $event->start_at->format('l F jS Y') !!} - {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
            data-lightbox="{{ $primary->path }}">
            <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}"
                alt="{{ $event->name }}"
                class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300">
        </a>
        @else
        <a href="/images/event-placeholder.png"
            data-lightbox="event-{{ $event->id }}">
            <div class="w-full aspect-square bg-card flex items-center justify-center">
                <i class="bi bi-calendar-event text-4xl text-muted-foreground"></i>
            </div>
        </a>
        @endif

        <!-- Favorite/Attend Button -->
        @if ($signedIn)
        <div class="absolute top-2 right-2">
            @if ($response = $event->getEventResponse($user))
            <a href="{!! route('events.unattend', ['id' => $event->id]) !!}"
                data-target="#event-card-{{ $event->id }}"
                class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                title="{{ $response->responseType->name }}">
                <i class="bi bi-star-fill text-primary text-lg"></i>
            </a>
            @else
            <a href="{!! route('events.attend', ['id' => $event->id]) !!}"
                data-target="#event-card-{{ $event->id }}"
                class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                title="Click to mark as attending">
                <i class="bi bi-star text-muted-foreground hover:text-primary text-lg"></i>
            </a>
            @endif
        </div>
        @endif

        <!-- Cancelled Badge -->
        @if ($event->visibility->name === 'Cancelled')
        <div class="absolute top-2 left-2">
            <span class="badge-tw badge-destructive-tw">Cancelled</span>
        </div>
        @elseif ($event->visibility->name !== 'Public')
        <div class="absolute top-2 left-2">
            <span class="badge-tw badge-warning-tw">{{ $event->visibility->name }}</span>
        </div>
        @endif
    </div>

    <!-- Card Content -->
    <div class="event-card-content-tw">
        <!-- Event Title -->
        <h3 class="event-card-title-tw mb-2 line-clamp-2">
            <a href="{{ route('events.show', [$event->slug]) }}">{{ $event->name }}</a>
        </h3>

        <!-- Short Description -->
        @if ($event->short)
        <p class="text-sm text-muted-foreground mb-3 line-clamp-2">{{ $event->short }}</p>
        @endif

        <!-- Event Type -->
        <div class="mb-3">
            <a href="/events/type/{{ $event->eventType->slug }}" class="text-sm text-muted-foreground hover:text-primary">
                {{ $event->eventType->name }}
            </a>
        </div>

        <!-- Event Meta Info -->
        <div class="space-y-2 text-sm text-muted-foreground mb-4">
            <!-- Date & Time -->
            <div class="flex items-center gap-2">
                <i class="bi bi-calendar3"></i>
                <a href="/events/by-date/{!! $event->start_at->format('Y') !!}/{!! $event->start_at->format('m') !!}/{!! $event->start_at->format('d') !!}" class="hover:text-primary">
                    {{ $event->start_at->format('l, F j, Y') }} at {{ $event->start_at->format('g:i A') }}
                    @if ($event->end_at)
                    - {{ $event->end_at->format('g:i A') }}
                    @endif
                </a>
            </div>

            <!-- Venue -->
            @if ($event->venue)
            <div class="flex items-center gap-2">
                <i class="bi bi-geo-alt"></i>
                <a href="/entities/{{ $event->venue->slug }}" class="hover:text-primary">{{ $event->venue->name }}</a>
                @if ($event->venue->getPrimaryLocationMap())
                <a href="{{ $event->venue->getPrimaryLocationMap() }}" target="_blank" rel="noopener" title="{{ $event->venue->getPrimaryLocationAddress() }}" class="text-primary hover:text-primary/90">
                    <i class="bi bi-box-arrow-up-right text-xs"></i>
                </a>
                @endif
            </div>
            @endif

            <!-- Age Restriction -->
            @if (isset($event->min_age))
            <div class="flex items-center gap-2">
                <i class="bi bi-person-badge"></i>
                <span>{{ $event->age_format }}</span>
            </div>
            @endif

            <!-- Price -->
            @if (isset($event->door_price) || isset($event->presale_price))
            <div class="flex items-center gap-2">
                <i class="bi bi-currency-dollar"></i>
                <span>
                    @if (isset($event->presale_price))
                    Door: ${{ floor($event->presale_price) == $event->presale_price ? number_format($event->presale_price, 0) : number_format($event->presale_price, 2) }}
                    @endif
                    @if (isset($event->door_price))
                    @if(isset($event->presale_price)) / @endif
                    ${{ floor($event->door_price) == $event->door_price ? number_format($event->door_price, 0) : number_format($event->door_price, 2) }}
                    @endif
                </span>
            </div>
            @endif
        </div>

        <!-- Related Entities Tags -->
        @unless ($event->entities->isEmpty())
        <div class="flex flex-wrap gap-1 mb-2">
            @foreach ($event->entities->take(3) as $entity)
                <x-entity-badge :entity="$entity" context="events" />
            @endforeach
            @if ($event->entities->count() > 3)
            <span class="badge-tw badge-secondary-tw text-xs">+{{ $event->entities->count() - 3 }} more</span>
            @endif
        </div>
        @endunless

        <!-- Tags -->
        @unless ($event->tags->isEmpty())
        <div class="flex flex-wrap gap-1 mt-auto pt-2">
            @foreach ($event->tags->take(5) as $tag)
                <x-tag-badge :tag="$tag" context="events" />
            @endforeach
            @if ($event->tags->count() > 5)
            <span class="text-xs text-muted-foreground">+{{ $event->tags->count() - 5 }} more</span>
            @endif
        </div>
        @endunless
    </div>

    <!-- Card Footer Actions -->
    <div class="px-4 py-3 border-t border-border flex items-center justify-between">
        <div class="flex items-center gap-2">
            <!-- Edit Button -->
            @if ($signedIn && ($event->ownedBy($user) || $user->hasGroup('super_admin')))
            <a href="{{ route('events.edit', ['event' => $event->slug]) }}"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="Edit this event">
                <i class="bi bi-pencil"></i>
            </a>
            @endif

            <!-- Thread Link -->
            @if ($thread = $event->threads->first())
            <a href="{{ route('threads.show', ['thread' => $thread->id]) }}"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="View discussion">
                <i class="bi bi-chat"></i>
            </a>
            @endif

            <!-- External Link -->
            @if ($link = $event->primary_link)
            <a href="{{ $link }}" target="_blank" rel="noopener"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="External link">
                <i class="bi bi-link-45deg"></i>
            </a>
            @endif

            <!-- Ticket Link -->
            @if ($ticket = $event->ticket_link)
            <a href="{{ $ticket }}" target="_blank" rel="noopener"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="Buy tickets">
                <i class="bi bi-ticket-perforated"></i>
            </a>
            @endif
        </div>

        <!-- Series Link -->
        @if (!empty($event->series_id))
        <a href="/series/{{ $event?->series?->slug }}" class="text-xs text-muted-foreground hover:text-primary">
            Part of {{ $event?->series?->name }}
        </a>
        @endif
    </div>
</article>
