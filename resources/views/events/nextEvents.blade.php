
	<div class="d-block" id="next-events">
		<div class="col-sm-12">
			<ul class="list-style-none"  class="mt-0">
				<li class="btn btn-primary">{!! link_to_route('events.add', 'Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'next-events page-link text-nowrap']) !!}</li>
			</ul>
		</div>
	</div>
	