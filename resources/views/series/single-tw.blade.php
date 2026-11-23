{{--
    One series in a list: a row (thumbnail beside the details) or, with 'card' => true,
    a card (thumbnail on top, plus the next edition) as the day columns of the home page use.

    $series  Series
    $card    bool   default false
--}}
@php
    $card = $card ?? false;
    $entityLimit = $card ? 3 : 4;
    $thumbSize = $card ? 'w-full' : 'w-20 sm:w-24 md:w-28';
@endphp
<li id="series-{{ $series->id }}" @class([
    'group relative flex flex-col bg-card rounded-lg border border-border hover:border-primary transition-colors shadow-sm min-w-0',
    'gap-3 p-3 h-full' => $card,
    'sm:flex-row gap-3 sm:gap-4 p-3 sm:p-4 mb-3 sm:mb-4' => ! $card,
])>
    <!-- Thumbnail -->
    <div @class(['flex-shrink-0', 'w-full' => $card])>
        @if ($primary = $series->getPrimaryPhoto())
        <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
            data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='{{ route('series.show', $series) }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venueLabel() ?? '' }}</a>"
            data-lightbox="{{ $primary->path }}"
            class="block aspect-square {{ $thumbSize }} rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors">
            <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $series->name }}" loading="lazy" class="w-full h-full object-cover">
        </a>
        @else
        <a href="/images/event-placeholder.png"
            data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='{{ route('series.show', $series) }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venueLabel() ?? '' }}</a>"
            data-lightbox="/images/event-placeholder.png"
            @class(['block aspect-square rounded-lg overflow-hidden border border-border group-hover:border-primary transition-colors flex items-center justify-center', $thumbSize, $card ? 'bg-muted' : 'bg-card'])>
            <i @class(['bi bi-collection text-muted-foreground/40', $card ? 'text-5xl' : 'text-4xl'])></i>
        </a>
        @endif
    </div>

    <!-- Content -->
    <div @class(['flex-grow min-w-0', 'flex flex-col' => $card])>
        <!-- Header -->
        <div @class([$card ? 'flex flex-col gap-1 mb-2' : 'flex flex-wrap items-start justify-between gap-2 mb-1'])>
            <!-- Occurrence Type -->
            <span class="text-xs font-bold text-primary uppercase tracking-wider">
                {{ $series->occurrenceType->name }} {{ $series->occurrence_repeat }}
            </span>

            @unless ($card)
            <!-- Actions -->
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                @include('series.single-actions-tw')
            </div>
            @endunless
        </div>

        <!-- Title -->
        <h3 @class(['font-bold text-foreground break-words', $card ? 'text-base sm:text-lg mb-2 line-clamp-2' : 'text-lg sm:text-xl mb-1'])>
            <a href='{{ route('series.show', $series) }}' class="hover:text-primary transition-colors">
                {{ $series->name }}
            </a>
        </h3>

        <!-- Venue -->
        @if ($series->venue || $series->venueLabel())
        <div @class(['text-muted-foreground mb-2 break-words', $card ? 'text-sm line-clamp-1' : 'text-sm sm:text-base'])>
            <span class="text-muted-foreground/70">@</span>
            @if ($series->venue)
            <a href='/entities/{{ $series->venue->slug }}' class="hover:text-primary transition-colors">
                {{ $series->venue->name }}
            </a>
            @else
            {{ $series->venueLabel() }}
            @endif
        </div>
        @endif

        <!-- Time -->
        <div @class(['flex flex-wrap items-center text-muted-foreground mb-2', $card ? 'gap-2 text-xs sm:text-sm' : 'gap-3 text-sm'])>
            @if ($series->start_at)
            <span class="flex items-center gap-1">
                <i class="bi bi-clock"></i>
                {{ $series->start_at->format('g:i A') }}
            </span>
            @endif
        </div>

        @if ($card)
        <!-- Next Edition Bar -->
        @php $nextEvent = $series->nextPublicEvent(); @endphp
        @if ($nextEvent)
        <div class="rounded border border-primary/30 bg-primary/5 px-2 py-1.5 flex items-start gap-1.5 mb-2">
            <i class="bi bi-calendar-event text-primary text-xs mt-0.5 flex-shrink-0"></i>
            <div class="min-w-0">
                <div class="text-xs font-semibold text-primary uppercase tracking-wide leading-none mb-0.5">Next Edition</div>
                <div class="text-xs text-foreground font-medium">{{ $nextEvent->start_at->format('D, M j, Y') }}</div>
                <a href="{{ route('events.show', [$nextEvent->slug]) }}" class="text-xs text-primary hover:underline truncate block leading-tight">{{ $nextEvent->name }}</a>
            </div>
        </div>
        @elseif ($series->occurrenceType->name !== 'No Schedule' && $series->cancelled_at === null && ($nextDate = $series->cycleFromFoundedAt()))
        <div class="rounded border border-border bg-muted/50 px-2 py-1.5 flex items-start gap-1.5 mb-2">
            <i class="bi bi-calendar3 text-muted-foreground text-xs mt-0.5 flex-shrink-0"></i>
            <div class="min-w-0">
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide leading-none mb-0.5">Next Edition</div>
                <div class="text-xs text-foreground font-medium">{{ $nextDate->format('D, M j, Y') }}</div>
                <span class="text-xs text-muted-foreground italic leading-tight">Not yet created</span>
            </div>
        </div>
        @endif
        @endif

        <!-- Entities -->
        @unless ($series->entities->isEmpty())
        <div class="flex flex-wrap gap-1 mb-2">
            @foreach ($series->entities->take($entityLimit) as $entity)
                <x-entity-badge :entity="$entity" context="series" />
            @endforeach
            @if ($series->entities->count() > $entityLimit)
            <span class="text-xs text-muted-foreground self-center">+{{ $series->entities->count() - $entityLimit }}</span>
            @endif
        </div>
        @endunless

        <!-- Tags -->
        @unless ($series->tags->isEmpty())
        <div @class(['flex flex-wrap gap-1', 'mt-auto' => $card])>
            @foreach ($series->tags->take(3) as $tag)
                <x-tag-badge :tag="$tag" context="series" />
            @endforeach
            @if ($series->tags->count() > 3)
            <span class="text-xs text-muted-foreground self-center">+{{ $series->tags->count() - 3 }}</span>
            @endif
        </div>
        @endunless

        @if ($card)
        <!-- Actions (visible on hover) -->
        <div class="flex items-center justify-center gap-2 mt-3 pt-3 border-t border-border opacity-0 group-hover:opacity-100 transition-opacity">
            @include('series.single-actions-tw')
        </div>
        @endif
    </div>
</li>
