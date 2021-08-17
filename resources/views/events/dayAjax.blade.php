<div class="col">
    <div class="card mb-4">

            <div class="card-header bg-primary">
                <h5 class="my-0 fw-normal">
				@if (\Carbon\Carbon::now('America/New_York')->format('Ymd') === $day)
				Today's Events
				@else
				{{ $day->format('l M jS Y') }}
				@endif
				</h3>
			</div>

			<div class="panel-body">
				<div class="load-spinner">
					<div class="double-bounce1"></div>
					<div class="double-bounce2"></div>
				</div>
			</div>
	</div>
</div>
