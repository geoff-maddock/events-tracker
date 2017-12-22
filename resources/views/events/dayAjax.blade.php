<div class="col-lg-3"">
	<div class="bs-component">
		<div class="panel panel-info">

			<div class="panel-heading">
				<h3 class="panel-title">
				@if ($offset == 0) 
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