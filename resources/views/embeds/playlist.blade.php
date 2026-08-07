@if (isset($embeds) && count($embeds) > 0)

<div class="rounded-lg border bg-card shadow p-6 space-y-4">
    <div class="flex items-center gap-2 mb-2">
        <i class="bi bi-music-note-beamed text-lg"></i>
        <h2 class="text-xl font-semibold">Audio</h2>
    </div>
    
    <div class="space-y-4">
        @foreach ($embeds as $embed)
        <div class="rounded-md overflow-hidden">    
            {!! $embed !!}
        </div>
        @endforeach
    </div>
</div>

@else

    {{-- @elseif, not three independent @ifs: @include inherits the caller's scope, so a
         stray $event/$entity from a surrounding @foreach must not render a second
         placeholder alongside the subject the caller actually passed. --}}
    @if (isset($event) && is_object($event))
        <div id="playlist-events-{{ $event->id}}" class="playlist-id rounded-lg border bg-card shadow p-6" data-url="/events/{{ $event->id }}/load-embeds" data-slug="{{ $event->slug }}" data-resource-type="events" data-endpoint="embeds">
            <div class="flex items-center gap-2 mb-4">
                <i class="bi bi-music-note-beamed text-lg"></i>
                <h3 class="text-xl font-semibold">Audio</h3>
            </div>
            <div class="flex items-center justify-center py-8">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @elseif (isset($entity) && is_object($entity))
        <div id="playlist-entities-{{ $entity->id}}" class="playlist-id rounded-lg border bg-card shadow p-6" data-url="/entities/{{ $entity->id }}/load-embeds" data-slug="{{ $entity->slug }}" data-resource-type="entities" data-endpoint="embeds">
            <div class="flex items-center gap-2 mb-4">
                <i class="bi bi-music-note-beamed text-lg"></i>
                <h3 class="text-xl font-semibold">Audio</h3>
            </div>
            <div class="flex items-center justify-center py-8">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @elseif (isset($series) && is_object($series))
        <div id="playlist-series-{{ $series->id}}" class="playlist-id rounded-lg border bg-card shadow p-6" data-url="/series/{{ $series->id }}/load-embeds" data-slug="{{ $series->slug }}" data-resource-type="series" data-endpoint="embeds">
            <div class="flex items-center gap-2 mb-4">
                <i class="bi bi-music-note-beamed text-lg"></i>
                <h3 class="text-xl font-semibold">Audio</h3>
            </div>
            <div class="flex items-center justify-center py-8">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @endif

@endif
