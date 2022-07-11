<div class="m-1">
    @if ($event = $photo->events->first())
    <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="grid"
        data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
        data-lightbox="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
        title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $photo->name }}" class="image-lg">
    </a>
    @else
    <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="grid"
        data-lightbox="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $photo->name }}" class="image-lg">
    </a>
    @endif
</div>