{{--
    One event in a list: a row (thumbnail beside the details) or, with 'card' => true,
    a card (thumbnail on top) as the day columns of the home page use.

    $event  Event
    $card   bool   default false
--}}
@php
    $card = $card ?? false;
    $entityLimit = $card ? 3 : 4;
    $tagLimit = $card ? 4 : 3;
    $thumbSize = $card ? 'w-full' : 'w-20 sm:w-24 md:w-28';
    $lightboxTitle = $event->display_start_at->format('l F jS Y')." <a href='/events/".e($event->slug)."'>".e($event->name)."</a> @ <a href='/entities/".e($event->venue ? $event->venue->slug : '')."'>".e($event->venueLabel() ?? '').'</a>';
@endphp
<li id="event-{{ $event->id }}" @class([
    'group relative flex flex-col bg-card rounded-lg border border-border hover:border-primary transition-colors shadow-sm min-w-0',
    'gap-3 p-3 h-full' => $card,
    'sm:flex-row gap-3 sm:gap-4 p-3 sm:p-4 mb-3 sm:mb-4' => ! $card,
])>
    <!-- Thumbnail -->
    <div @class(['flex-shrink-0', 'w-full relative' => $card])>
        @if ($primary = $event->getPrimaryPhoto())
        <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
            data-title="{{ $lightboxTitle }}"
            data-lightbox="{{ $primary->path }}"
            class="block aspect-square {{ $thumbSize }} rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors">
            <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $event->name }}" loading="lazy" class="w-full h-full object-cover">
        </a>
        @else
        <a href="/images/event-placeholder.png"
            data-title="{{ $lightboxTitle }}"
            data-lightbox="/images/event-placeholder.png"
            aria-label="{{ $event->name }}"
            @class(['block aspect-square rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors flex items-center justify-center', $thumbSize, $card ? 'bg-muted' : 'bg-card'])>
            <i @class(['bi bi-calendar-event text-muted-foreground/40', $card ? 'text-5xl' : 'text-4xl']) aria-hidden="true"></i>
        </a>
        @endif

        @if ($card)
        <!-- Favorite/Attend Button -->
        <div class="absolute top-2 right-2">
            @if ($signedIn)
                @if ($response = $event->getEventResponse($user))
                <a href="{!! route('events.unattend', ['id' => $event->id]) !!}"
                    data-target="#event-{{ $event->id }}"
                    class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                    title="{{ $response->responseType->name }}">
                    <i class="bi bi-star-fill text-primary text-lg"></i>
                </a>
                @else
                <a href="{!! route('events.attend', ['id' => $event->id]) !!}"
                    data-target="#event-{{ $event->id }}"
                    class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                    title="Click to mark as attending">
                    <i class="bi bi-star text-muted-foreground hover:text-primary text-lg"></i>
                </a>
                @endif
            @else
                <a href="{!! route('login') !!}"
                    class="p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
                    title="Sign in to mark as attending">
                    <i class="bi bi-star text-muted-foreground hover:text-primary text-lg"></i>
                </a>
            @endif
        </div>
        @endif
    </div>

    <!-- Content -->
    <div @class(['flex-grow min-w-0', 'flex flex-col' => $card])>
        <!-- Header -->
        <div @class([$card ? 'flex flex-col gap-1 mb-2' : 'flex flex-wrap items-start justify-between gap-2 mb-1'])>
            <div class="flex flex-col gap-1">
                <!-- Visibility Badge -->
                @if ($event->visibility->name !== 'Public')
                <span class="text-xs font-bold text-warning uppercase tracking-wider">{{ $event->visibility->name }}</span>
                @endif

                <!-- Date -->
                <a href='/events/by-date/{!! $event->display_start_at->format('Y') !!}/{!! $event->display_start_at->format('m') !!}/{!! $event->display_start_at->format('d') !!}'
                   @class(['text-primary hover:text-primary/90 font-medium', $card ? 'text-xs sm:text-sm' : 'text-sm'])>
                    {!! $event->display_start_at->format('D F jS Y') !!}
                </a>
                @if ($event->isMultiDate())
                <a href="/events/{{ $event->slug }}" class="text-xs text-muted-foreground hover:text-primary whitespace-nowrap" title="This event has more than one date">+ more dates</a>
                @endif
            </div>

            @unless ($card)
            <!-- Actions -->
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                @include('events.single-actions-tw')

                @if ($signedIn)
                    @if ($response = $event->getEventResponse($user))
                    <a data-method="post" href="{!! route('events.unattend', ['id' => $event->id]) !!}"
                       class="text-primary hover:text-destructive transition-colors"
                       title="Unattend">
                        <i class="bi bi-check-circle-fill"></i>
                    </a>
                    @else
                    <a data-method="post" href="{!! route('events.attend', ['id' => $event->id]) !!}"
                       class="text-muted-foreground hover:text-primary transition-colors"
                       title="Attend">
                        <i class="bi bi-check-circle"></i>
                    </a>
                    @endif
                @endif
            </div>
            @endunless
        </div>

        <!-- Title -->
        <{{ $heading ?? 'h3' }} @class(['font-bold text-foreground break-words', $card ? 'text-base sm:text-lg mb-2 line-clamp-2' : 'text-lg sm:text-xl mb-1'])>
            <a href='/events/{{ $event->slug }}' class="hover:text-primary transition-colors">
                {{ $event->name }}
            </a>
        </{{ $heading ?? 'h3' }}>

        <!-- Venue -->
        @if ($event->venue || $event->venueLabel())
        <div @class(['text-muted-foreground mb-2 break-words', $card ? 'text-sm line-clamp-1' : 'text-sm sm:text-base'])>
            <span class="text-muted-foreground/70">@</span>
            @if ($event->venue)
            <a href='/entities/{{ $event->venue->slug }}' class="hover:text-primary transition-colors">
                {{ $event->venue->name }}
            </a>
            @else
            {{ $event->venueLabel() }}
            @endif
        </div>
        @endif

        <!-- Time & Price -->
        <div @class(['flex flex-wrap items-center text-muted-foreground mb-2', $card ? 'gap-2 text-xs sm:text-sm' : 'gap-3 text-sm'])>
            @if ($event->display_start_at)
            <span class="flex items-center gap-1">
                <i class="bi bi-clock"></i>
                {{ $event->display_start_at->format('g:i A') }}
            </span>
            @endif
            
            @if ($price = $event->doorPriceLabel())
            <span class="flex items-center gap-1">
                <i class="bi bi-currency-dollar"></i>
                {{ $price }}
            </span>
            @endif

            @if ($ticket = $event->getTicketTrackingLink())
            <a href="{{ $ticket }}"
               class="flex items-center gap-1 text-muted-foreground hover:text-primary transition-colors"
               title="Buy tickets" target="_blank" rel="noopener">
                <i class="bi bi-ticket-perforated"></i>
                <span>Buy Tickets</span>
            </a>
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
            @foreach ($event->entities->take($entityLimit) as $entity)
                <x-entity-badge :entity="$entity" context="events" />
            @endforeach
            @if ($event->entities->count() > $entityLimit)
            <span class="text-xs text-muted-foreground self-center">+{{ $event->entities->count() - $entityLimit }}</span>
            @endif
        </div>
        @endunless

        <!-- Tags -->
        @unless ($event->tags->isEmpty())
        <div class="flex flex-wrap gap-1">
            @foreach ($event->tags->take($tagLimit) as $tag)
                <x-tag-badge :tag="$tag" context="events" />
            @endforeach
            @if ($event->tags->count() > $tagLimit)
            <span class="text-xs text-muted-foreground self-center">+{{ $event->tags->count() - $tagLimit }}</span>
            @endif
        </div>
        @endunless

        @if ($card)
        <!-- Actions (visible on hover) -->
        <div class="flex items-center justify-center gap-2 mt-3 pt-3 border-t border-border opacity-0 group-hover:opacity-100 transition-opacity">
            @include('events.single-actions-tw')
        </div>
        @endif
    </div>
</li>
