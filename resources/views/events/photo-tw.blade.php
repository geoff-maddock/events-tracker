@foreach ($event->photos as $photo)
    @if (!$photo->is_primary)
        <div class="group relative aspect-square overflow-hidden rounded-lg bg-muted">
            <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
                data-lightbox="grid"
                data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->slug }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
                title="{{ $event->start_at->format('l F jS Y') }} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}">
                <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}"
                    alt="{{ $event->name }}"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="absolute bottom-0 left-0 right-0 p-2">
                        <p class="text-white text-xs font-medium truncate">{{ $event->name }}</p>
                        <p class="text-white/80 text-xs">{{ $event->start_at->format('M j, Y') }}</p>
                    </div>
                </div>
            </a>
        </div>
    @endif
@endforeach
