{{-- Past Events Grid Card --}}
{{-- Displays a compact image grid of past events within the space of a single event card, --}}
{{-- matching the style of the events/grid-tw view (cell-compact-tw cells).              --}}
@php
    $hasMore = $pastEvents->count() > 20;
    $displayEvents = $pastEvents->take(20);
    $count = $pastEvents->count();
    if ($count <= 4) {
        $colSpan   = 'col-span-1';
        $innerCols = 'grid-cols-2';
    } elseif ($count <= 8) {
        $colSpan   = 'col-span-1 md:col-span-2';
        $innerCols = 'grid-cols-3 md:grid-cols-4';
    } else {
        $colSpan   = 'col-span-1 md:col-span-2 event-3col:col-span-3 event-4col:col-span-4';
        $innerCols = 'grid-cols-4 md:grid-cols-6';
    }
@endphp
<article class="event-card-tw h-full flex flex-col {{ $colSpan }}" id="past-events-grid-card">
    {{-- Card Header --}}
    <div class="px-3 py-2 border-b border-border flex items-center gap-2 flex-shrink-0">
        <i class="bi bi-clock-history text-muted-foreground text-sm"></i>
        <h4 class="font-semibold text-sm">Past Events</h4>
        <span class="ml-auto text-xs text-muted-foreground badge-tw badge-secondary-tw">
            {{ $displayEvents->count() }}{{ $hasMore ? '+' : '' }}
        </span>
    </div>

    {{-- Inner compact grid of event cells --}}
    <div class="flex-1 overflow-y-auto p-2">
        <div class="grid gap-1 {{ $innerCols }}">
            @php $lastDate = ''; @endphp
            @foreach ($displayEvents as $pastEvent)
                @php
                    $currentDate = $pastEvent->start_at->format('Y-m-d');
                    $showDateBar = $currentDate !== $lastDate;
                    if ($showDateBar) { $lastDate = $currentDate; }
                    $isWeekend = $pastEvent->start_at->isWeekend() || $pastEvent->start_at->isFriday();
                    $dateLabel = $pastEvent->start_at->format('M j');
                @endphp
                {{-- Cell: mirrors cell-compact-tw --}}
                <div class="flex flex-col group">
                    {{-- Date bar --}}
                    @if ($showDateBar)
                        <div class="text-xs font-medium px-1 py-0.5 text-center mb-0.5 rounded-sm {{ $isWeekend ? 'badge-tw badge-warning-tw' : 'bg-accent text-foreground' }}">
                            {{ $dateLabel }}
                        </div>
                    @else
                        <div class="text-xs font-medium px-1 py-0.5 text-center mb-0.5 invisible">{{ $dateLabel }}</div>
                    @endif

                    {{-- Image with hover overlay --}}
                    <div class="w-full aspect-square overflow-hidden relative bg-background rounded-md border border-border">
                        <div class="w-full h-full">
                            @if ($primary = $pastEvent->getPrimaryPhoto())
                                <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
                                   data-lightbox="past-events-grid"
                                   data-title="{{ $pastEvent->name }} @ {{ $pastEvent->venue ? $pastEvent->venue->name : '' }}">
                                    <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}"
                                         alt="{{ $pastEvent->name }}"
                                         class="w-full h-full object-cover transition-transform duration-300"
                                         loading="lazy">
                                </a>
                            @else
                                <img src="/images/event-placeholder.png"
                                     alt="{{ $pastEvent->name }}"
                                     class="w-full h-full object-cover opacity-50">
                            @endif
                        </div>

                        {{-- Hover overlay: event name, type, venue, tags --}}
                        <div class="absolute inset-0 bg-black/80 flex flex-col items-center justify-center p-1 text-white text-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                            <div class="text-xs font-semibold mb-0.5 line-clamp-2 leading-tight">
                                {{ $pastEvent->name }}
                            </div>
                            @if ($pastEvent->eventType)
                                <div class="text-xs opacity-80 mb-0.5">
                                    {{ $pastEvent->eventType->name }}
                                </div>
                            @endif
                            @if ($pastEvent->venue)
                                <div class="text-xs opacity-70 line-clamp-1">
                                    {{ $pastEvent->venue->name }}
                                </div>
                            @endif
                            @if ($pastEvent->tags->count() > 0)
                                <div class="flex flex-wrap gap-0.5 justify-center mt-0.5">
                                    @foreach ($pastEvent->tags->take(2) as $tag)
                                        <x-tag-badge :tag="$tag" context="events" variant="primary" />
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Details button --}}
                    <a href="{{ route('events.show', ['event' => $pastEvent->slug]) }}"
                       class="mt-0.5 w-full px-1 py-1 text-xs font-medium text-center text-foreground bg-transparent border border-border rounded hover:bg-card transition-colors">
                        Details
                    </a>
                </div>
            @endforeach
        </div>
        @if ($hasMore)
        <p class="text-xs text-muted-foreground text-center pt-2">
            Showing 20 most recent &mdash; <a href="{{ url('events/related-to/'.$entity->slug) }}" class="text-primary hover:text-primary/80">view all past events</a>
        </p>
        @endif
    </div>

    {{-- Card Footer --}}
    <div class="px-3 py-2 border-t border-border flex-shrink-0">
        <a href="{{ url('events/related-to/'.$entity->slug) }}"
           class="text-xs text-primary hover:text-primary/80 transition-colors inline-flex items-center gap-1">
            View All Related Events
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>

</article>
