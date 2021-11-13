<div class="m-1">
    @if ($event = $photo->events->first())
    <a href="{{ $photo->getStoragePath() }}" data-lightbox="grid"
        data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
        data-lightbox="{{ $photo->path }}"
        title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $photo->name }}" class="image-lg">
    </a>
    @else
    <a href="{{ $photo->getStoragePath() }}" data-lightbox="grid"
        data-lightbox="{{ $photo->path }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $photo->name }}" class="image-lg">
    </a>
    @endif
</div>