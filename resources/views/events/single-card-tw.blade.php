<li id="event-{{ $event->id }}" class="group relative flex flex-col gap-3 p-3 bg-card rounded-lg border border-border hover:border-primary transition-colors shadow-sm min-w-0 h-full">
    <!-- Thumbnail -->
    <div class="flex-shrink-0 w-full">
        @if ($primary = $event->getPrimaryPhoto())
        <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
            data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->slug }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
            data-lightbox="{{ $primary->path }}"
            class="block aspect-square w-full rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors">
            <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
        </a>
        @else
        <a href="/images/event-placeholder.png"
            data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->slug }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
            data-lightbox="/images/event-placeholder.png"
            class="block aspect-square w-full rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors bg-muted flex items-center justify-center">
            <i class="bi bi-calendar-event text-5xl text-muted-foreground/40"></i>
        </a>
        @endif
    </div>

    <!-- Content -->
    <div class="flex-grow min-w-0 flex flex-col">
        <!-- Header -->
        <div class="flex flex-col gap-1 mb-2">
            <!-- Visibility Badge -->
            @if ($event->visibility->name !== 'Public')
            <span class="text-xs font-bold text-yellow-500 uppercase tracking-wider">{{ $event->visibility->name }}</span>
            @endif
            
            <!-- Date -->
            <a href='/events/by-date/{!! $event->start_at->format('Y') !!}/{!! $event->start_at->format('m') !!}/{!! $event->start_at->format('d') !!}'
               class="text-xs sm:text-sm text-primary hover:text-primary/90 font-medium">
                {!! $event->start_at->format('D F jS Y') !!}
            </a>
        </div>

        <!-- Title -->
        <h3 class="text-base sm:text-lg font-bold text-foreground mb-2 break-words line-clamp-2">
            <a href='/events/{{ $event->slug }}' class="hover:text-primary transition-colors">
                {{ $event->name }}
            </a>
        </h3>

        <!-- Venue -->
        @if ($event->venue)
        <div class="text-sm text-muted-foreground mb-2 break-words line-clamp-1">
            <span class="text-muted-foreground/70">@</span>
            <a href='/entities/{{ $event->venue->slug }}' class="hover:text-primary transition-colors">
                {{ $event->venue->name }}
            </a>
        </div>
        @endif

        <!-- Time & Price -->
        <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm text-muted-foreground mb-2">
            @if ($event->start_at)
            <span class="flex items-center gap-1">
                <i class="bi bi-clock"></i>
                {{ $event->start_at->format('g:i A') }}
            </span>
            @endif
            
            @if ($event->door_price)
            <span class="flex items-center gap-1">
                <i class="bi bi-currency-dollar"></i>
                {{ $event->door_price }}
            </span>
            @endif
            
            @if($event->min_age)
            <span class="flex items-center gap-1">
                <i class="bi bi-person-badge"></i>
                {{ $event->min_age }}+
            </span>
            @endif
        </div>

        <!-- Entities -->
        @unless ($event->entities->isEmpty())
        <div class="flex flex-wrap gap-1 mb-2">
            @foreach ($event->entities->take(3) as $entity)
                <x-entity-badge :entity="$entity" context="events" size="sm" />
            @endforeach
            @if ($event->entities->count() > 3)
            <span class="text-xs text-muted-foreground self-center">+{{ $event->entities->count() - 3 }}</span>
            @endif
        </div>
        @endunless

        <!-- Tags -->
        @unless ($event->tags->isEmpty())
        <div class="flex flex-wrap gap-1 mt-auto">
            @foreach ($event->tags->take(3) as $tag)
                <x-tag-badge :tag="$tag" context="events" size="sm" />
            @endforeach
            @if ($event->tags->count() > 3)
            <span class="text-xs text-muted-foreground self-center">+{{ $event->tags->count() - 3 }}</span>
            @endif
        </div>
        @endunless

        <!-- Actions (visible on hover) -->
        <div class="flex items-center justify-center gap-2 mt-3 pt-3 border-t border-border opacity-0 group-hover:opacity-100 transition-opacity">
            @if ($signedIn && ($event->ownedBy($user) || $user->hasGroup('super_admin')))
            <a href="{!! route('events.edit', ['event' => $event->slug]) !!}"
                class="text-muted-foreground hover:text-primary transition-colors"
                title="Edit this event">
                <i class="bi bi-pencil-fill"></i>
            </a>
            @endif

            @if ($thread = $event->threads->first())
            <a href="{!! route('threads.show', ['thread' => $thread->id]) !!}"
               class="text-muted-foreground hover:text-primary transition-colors"
               title="Show related thread">
                <i class="bi bi-chat-fill"></i>
            </a>
            @endif

            @if ($link = $event->primary_link)
            <a href="{{ $link }}"
               class="text-muted-foreground hover:text-primary transition-colors"
               title="External link" target="_blank" rel="noopener">
                <i class="bi bi-link-45deg text-xl"></i>
            </a>
            @endif

            @if ($ticket = $event->ticket_link)
            <a href="{{ $ticket }}"
               class="text-muted-foreground hover:text-primary transition-colors"
               title="Ticket link" target="_blank" rel="noopener">
                <i class="bi bi-ticket-fill"></i>
            </a>
            @endif

            @if ($signedIn)
                @if ($response = $event->getEventResponse($user))
                <a href="{!! route('events.unattend', ['id' => $event->id]) !!}"
                   class="text-primary hover:text-destructive transition-colors"
                   title="Unattend">
                    <i class="bi bi-check-circle-fill"></i>
                </a>
                @else
                <a href="{!! route('events.attend', ['id' => $event->id]) !!}"
                   class="text-muted-foreground hover:text-primary transition-colors"
                   title="Attend">
                    <i class="bi bi-check-circle"></i>
                </a>
                @endif
            @endif
        </div>
    </div>
</li>
