@if (isset($embeds) && count($embeds) > 0)
<div class="row">
    <div class="col-lg-12">
        <div class="">      
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
        </span>
    </div>
</div>
@else
    @if (isset($event) && is_object($event) && !($event instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-{{ $event->id}}" class="playlist-id" data-url="/events/{{ $event->id }}/load-minimal-embeds">
            <div class="card-body">
			</div>
        </div>
    @endif
    @if (isset($series) && is_object($series) && !($series instanceof \Illuminate\Pagination\LengthAwarePaginator))
    <div id="playlist-{{ $series->id}}" class="playlist-id" data-url="/series/{{ $series->id }}/load-minimal-embeds">
        <div class="card-body">
        </div>
    </div>
    @endif
    @if (isset($entity) && is_object($entity) && !($entity instanceof \Illuminate\Pagination\LengthAwarePaginator))
        <div id="playlist-{{ $entity->id}}" class="playlist-id" data-url="/entities/{{ $entity->id }}/load-minimal-embeds">
            <div class="card-body">
            </div>
        </div>
    @endif
@endif
