<div class="flex flex-col group">
    <!-- Date bar or placeholder to maintain alignment -->
    @if ($showDateBar)
        <div class="text-xs font-medium px-2 py-1 text-center mb-1 rounded-sm {{ $isWeekend ? 'badge-tw badge-warning-tw' : 'bg-accent text-foreground' }}">
            {{ $dateLabel }}
        </div>
    @else
        <div class="text-xs font-medium px-2 py-1 text-center mb-1 invisible">
            Placeholder
        </div>
    @endif
    
    <!-- Image container - responsive size -->
    <div class="w-full aspect-square overflow-hidden relative bg-background rounded-md border border-border">
        <div class="w-full h-full">
            @if ($primary = $event->getPrimaryPhoto())
                <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}" 
                   data-lightbox="grid"
                   data-title="{{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}">
                    <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" 
                         alt="{{ $event->name }}" 
                         class="w-full h-full object-cover transition-transform duration-300">
                </a>
            @else
                <img src="/images/event-placeholder.png" 
                     alt="{{ $event->name }}" 
                     class="w-full h-full object-cover opacity-50">
            @endif
        </div>
        
        <!-- Hover overlay with event type and tags -->
        <div class="absolute inset-0 bg-black/80 flex flex-col items-center justify-center p-2 text-white text-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
            @if ($event->eventType)
                <div class="text-xs font-bold mb-1">
                    {{ $event->eventType->name }}
                </div>
            @endif
            
            @if ($event->tags->count() > 0)
                <div class="flex flex-wrap gap-1 justify-center">
                    @foreach ($event->tags->take(2) as $tag)
                        <span class="badge-tw badge-primary-tw text-xs">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Details button -->
    <a href="{{ route('events.show', ['event' => $event->slug]) }}"
       class="mt-1 w-full px-3 py-1.5 text-xs font-medium text-center text-foreground bg-transparent border border-border rounded hover:bg-card transition-colors">
        Details
    </a>
</div>