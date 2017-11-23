@if ($primary = $event->getPrimaryPhoto())
    <div style="padding: 5px;">
        <a href="/{{ $primary->path }}" data-lightbox="grid" data-title="{!! $event->start_at->format('l F jS Y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue->id or '' }}'>{{ $event->venue->name or '' }}</a>" data-lightbox="{{ $primary->path }}" title="{!! $event->start_at->format('l F jS Y') !!} {{ $event->name }} @ {{ $event->venue->name or '' }}" data-toggle="tooltip" data-placement="bottom">
            <img src="/{{ $primary->thumbnail }}" alt="{{ $event->name }}"  >
        </a>
    </div>
@endif


