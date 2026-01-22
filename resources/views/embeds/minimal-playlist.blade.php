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
    @if (isset($event) && is_object($event) && !($event instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-{{ $event->id}}" class="playlist-id rounded-lg border bg-card shadow p-4" data-url="/events/{{ $event->id }}/load-minimal-embeds">
            <div class="flex items-center justify-center py-4">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @endif
    @if (isset($series) && is_object($series) && !($series instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-{{ $series->id}}" class="playlist-id rounded-lg border bg-card shadow p-4" data-url="/series/{{ $series->id }}/load-minimal-embeds">
            <div class="flex items-center justify-center py-4">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @endif
    @if (isset($entity) && is_object($entity) && !($entity instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-{{ $entity->id}}" class="playlist-id rounded-lg border bg-card shadow p-4 {{ $entity->name }}" data-url="/entities/{{ $entity->id }}/load-minimal-embeds">
            <div class="flex items-center justify-center py-4">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @endif
@endif
