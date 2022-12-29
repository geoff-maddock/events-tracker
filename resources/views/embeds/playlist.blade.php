@if (count($embeds) > 0)
<div class="row">
    <div class="col-lg-12">
        <div class="card bg-dark">

            <h5 class="card-header bg-primary">Audio</h5>
        
            @foreach ($embeds as $embed)
            <div class="p-1">    
            {!! $embed !!}
            </div>
            @endforeach

        </div>
    </div>
</div>
@else
    @if (isset($event))
        <div id="playlist-{{ $event->id}}" class="playlist-id" data-url="/events/{{ $event->id }}/load-embeds">
            <div class="card-body">
				<div class="load-spinner">
					<div class="double-bounce1"></div>
					<div class="double-bounce2"></div>
				</div>
			</div>
        </div>
    @endif
    @if (isset($entity))
        <div id="playlist-{{ $entity->id}}" class="playlist-id" data-url="/entities/{{ $entity->id }}/load-embeds">
            <div class="card-body">
                <div class="load-spinner">
                    <div class="double-bounce1"></div>
                    <div class="double-bounce2"></div>
                </div>
            </div>
        </div>
    @endif
@endif
