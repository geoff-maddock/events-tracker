<div class="col-lg-3">
	<div class="bs-component">
		<div class="panel panel-info">

			<div class="panel-heading">
				<h3 class="panel-title">
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
</div>
