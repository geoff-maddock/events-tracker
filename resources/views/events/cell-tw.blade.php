<div class="group bg-card rounded-lg overflow-hidden shadow-lg border border-border hover:border-primary/50 hover:shadow-primary/10 transition-all duration-200 h-full flex flex-col">
    <!-- Image Section -->
    <div class="relative aspect-[4/3] overflow-hidden bg-background">
        @if ($primary = $event->getPrimaryPhoto())
            <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}" 
               data-lightbox="grid"
               data-title="{{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}">
                <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" 
                     alt="{{ $event->name }}" 
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            </a>
        @else
            <img src="/images/event-placeholder.png" 
                 alt="{{ $event->name }}" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 opacity-50">
        @endif

        <!-- Price Badge -->
        @if ($event->door_price)
            <div class="absolute bottom-2 left-2 bg-green-600 text-white text-xs px-2 py-1 rounded-md font-medium shadow-sm">
                ${{ $event->door_price }}
            </div>
        @endif
    </div>

    <div class="p-3 flex-1 flex flex-col">
        <!-- Event Title -->
        <h3 class="line-clamp-2 text-sm font-semibold leading-tight text-foreground mb-2 group-hover:text-primary transition-colors">
            <a href="{{ route('events.show', ['event' => $event->slug]) }}">
                {{ $event->name }}
            </a>
        </h3>

        <!-- Date -->
        <div class="flex items-center text-xs text-muted-foreground mb-1">
            <i class="bi bi-calendar3 mr-1.5"></i>
            {{ $event->start_at->format('D, M j, Y g:i A') }}
        </div>

        <!-- Venue -->
        @if ($event->venue)
            <div class="flex items-center text-xs text-muted-foreground mb-2">
                <i class="bi bi-geo-alt mr-1.5 flex-shrink-0"></i>
                <a href="{{ route('entities.show', ['entity' => $event->venue->slug]) }}" 
                   class="hover:text-primary transition-colors truncate">
                    {{ $event->venue->name }}
                </a>
            </div>
        @endif

        <!-- Tags -->
        @if ($event->tags->count() > 0)
            <div class="mt-auto pt-2 flex flex-wrap gap-1">
                @foreach ($event->tags->take(3) as $tag)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-card text-muted-foreground border border-border">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</div>