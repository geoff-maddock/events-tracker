@if ($primary = $event->getPrimaryPhoto())
<div style="padding: 5px;">
    <a href="{{ $primary->getStoragePath() }}" data-lightbox="grid"
        data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
        data-lightbox="{{ $primary->path }}"
        title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="{{ $primary->getStorageThumbnail() }}" alt="{{ $event->name }}" class="image-lg">
    </a>
</div>
@else
<div style="padding: 5px;">
    <a href="/images/event-placeholder.png" data-lightbox="grid"
        data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>"
        data-lightbox="/images/event-placeholder.png"
        title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
        data-toggle="tooltip" data-placement="bottom">
        <img src="/images/event-placeholder.png" alt="{{ $event->name }}" class="image-lg">
    </a>
</div>
@endif