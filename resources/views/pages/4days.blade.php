<div class="row">
	<div class="col-sm-3">
	<ul class="pagination float-start" class="mt-0">
		<li>{!! link_to_route('events.upcoming', '« Past Week', ['date' => $prev_day_window->format('Ymd')], ['class' => 'page-link text-nowrap']) !!}</li>
		<li>{!! link_to_route('events.upcoming', '« Past Day', ['date' => $prev_day->format('Ymd')], ['class' => 'page-link text-nowrap']) !!}</li>
	</ul>
	</div>
	
	<div class="col-sm-6">
	<ul class="pagination" class="mt-0">
	<li></li>
	</ul>
	</div>
	
	<div class="col-sm-3">
	<ul class="pagination float-end"  class="mt-0">
		<li>{!! link_to_route('events.upcoming', 'Future Day »', ['date' => $next_day->format('Ymd')], ['class' => 'page-link text-nowrap']) !!}</li>
		<li>{!! link_to_route('events.upcoming', 'Future Week »', ['date' => $next_day_window->format('Ymd')], ['class' => 'page-link text-nowrap']) !!}</li>
	</ul>
	</div>
	</div>

	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
	<?php $today = \Carbon\Carbon::now('America/New_York'); ?>
	
	<div class="row small-gutter row-cols-lg-4 home" id="home">
		@for ($offset = 0; $offset < 4; $offset++)
		<?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
			<section class="day">
				@include('events.day', ['day' => $day, 'position' => $offset ])
			</section>
		@endfor
	</div>

	<div class="d-block" id="next-events">
		<div class="col-sm-12">
			<ul class="list-style-none"  class="mt-0">
				<li>{!! link_to_route('events.add', 'Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'btn btn-primary next-events text-nowrap']) !!}</li>
			</ul>
		</div>
	</div>
	