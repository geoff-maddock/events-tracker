@if ($primary = $event->getPrimaryPhoto())
    <div style="padding: 5px;">
        <a href="/{{ $primary->path }}" data-lightbox="{{ $primary->path }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="/{{ $primary->thumbnail }}" alt="{{ $event->name}}"  ></a>
    </div>
@endif


