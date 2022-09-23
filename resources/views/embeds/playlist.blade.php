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
@endif
