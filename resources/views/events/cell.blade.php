@if ($primary = $event->getPrimaryPhoto())
    <div style="padding: 5px;">
        <a href="/{{ $primary->path }}" data-lightbox="grid" data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venue ? $event->venue->name : '' }}</a>" data-lightbox="{{ $primary->path }}" title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}" data-toggle="tooltip" data-placement="bottom">
            <img src="/{{ $primary->thumbnail }}" alt="{{ $event->name }}"  class="image-lg">
        </a>
    </div>
@endif


