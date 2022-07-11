@if ($primary = $event->getPrimaryPhoto())
<div class="m-2">
    <a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}" data-lightbox="grid"
        data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
        data-lightbox="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
        title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $event->name }}" class="image-lg">
    </a>
</div>
@else
<div class="m-2">
    <a href="/images/event-placeholder.png" data-lightbox="grid"
        data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
        data-lightbox="/images/event-placeholder.png"
        title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="/images/event-placeholder.png" alt="{{ $event->name }}" class="image-lg">
    </a>
</div>
@endif