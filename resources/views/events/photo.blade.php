@foreach ($event->photos as $primary)
@if (!$primary->is_primary)
<div class="p-2">
    <a href="{{ $primary->getStoragePath() }}" data-lightbox="grid"
        data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
        data-lightbox="{{ $primary->path }}"
        title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="{{ $primary->getStorageThumbnail() }}" alt="{{ $event->name }}" class="image-lg">
    </a>
</div>
@endif
@endforeach