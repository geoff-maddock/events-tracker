<div class="group relative overflow-hidden rounded-lg border border-border bg-card hover:shadow-lg transition-shadow">
    @if ($event = $photo->events->first())
    <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
       data-lightbox="grid"
       data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
       title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
       class="block aspect-square">
        <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}"
             alt="{{ $photo->name }}"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
    </a>
    @else
    <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
       data-lightbox="grid"
       class="block aspect-square">
        <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}"
             alt="{{ $photo->name }}"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
    </a>
    @endif
</div>
