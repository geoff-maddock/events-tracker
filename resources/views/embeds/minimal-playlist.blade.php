@if (isset($embeds) && count($embeds) > 0)
<div>      
    @php 
        $count = 0;
        $limit = 1;
    @endphp 
    @foreach ($embeds as $embed)
        {!! $embed !!}
        @php
            $count++
        @endphp
        @if ($count >= $limit)
            @php break; @endphp
        @endif
    @endforeach
</div>
@else
    {{-- @elseif, not three independent @ifs: @include inherits the caller's scope, so a
         stray $event/$entity from a surrounding @foreach must not render a second
         placeholder alongside the subject the caller actually passed. --}}
    @if (isset($event) && is_object($event) && !($event instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-events-{{ $event->id}}" class="playlist-id rounded-lg border bg-card shadow p-4 hidden" data-url="/events/{{ $event->id }}/load-minimal-embeds" data-slug="{{ $event->slug }}" data-resource-type="events">
            <div class="flex items-center justify-center py-4">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @elseif (isset($series) && is_object($series) && !($series instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-series-{{ $series->id}}" class="playlist-id rounded-lg border bg-card shadow p-4 hidden" data-url="/series/{{ $series->id }}/load-minimal-embeds" data-slug="{{ $series->slug }}" data-resource-type="series">
            <div class="flex items-center justify-center py-4">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @elseif (isset($entity) && is_object($entity) && !($entity instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-entities-{{ $entity->id}}" class="playlist-id rounded-lg border bg-card shadow p-4 hidden {{ $entity->name }}" data-url="/entities/{{ $entity->id }}/load-minimal-embeds" data-slug="{{ $entity->slug }}" data-resource-type="entities">
            <div class="flex items-center justify-center py-4">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @endif
@endif
