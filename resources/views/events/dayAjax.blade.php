<div class="col">
    <div class="card surface-container mb-4">

            <div class="card-header bg-primary">
                <h5 class="my-0 fw-normal">
					@if (\Carbon\Carbon::now('America/New_York')->format('Ymd') === $day->format('Ymd'))
					Today's Events
					@else
					{{ $day->format('l F jS Y') }}
					@endif
				</h3>
			</div>

			<div class="card-body">
				<div class="load-spinner">
					<div class="double-bounce1"></div>
					<div class="double-bounce2"></div>
				</div>
			</div>
	</div>
</div>
