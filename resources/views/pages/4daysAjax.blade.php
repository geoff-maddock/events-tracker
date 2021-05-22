<div class="row">
	<div class="col-sm-3">
	<ul class="pagination pull-left" style="margin-top: 0px;">
		<li>{!! link_to_route('home', '< Past Week', ['day_offset' => $dayOffset-4], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
		<li>{!! link_to_route('home', '< Past Day', ['day_offset' => $dayOffset-1], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
	</ul>
	</div>
	
	<div class="col-sm-6">
	<ul class="pagination" style="margin-top: 0px;">
	<li></li>
	</ul>
	</div>
	
	<div class="col-sm-3">
	<ul class="pagination pull-right" style="margin-top: 0px;">
		<li>{!! link_to_route('home', 'Future Day >', ['day_offset' => $dayOffset+1], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
		<li>{!! link_to_route('home', 'Future Week >', ['day_offset' => $dayOffset+4], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
	</ul>
	</div>
	</div>
	<br style="clear: left;"/>
	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
	<?php $today = \Carbon\Carbon::now('America/New_York'); ?>
	
	<div class="row small-gutter">
		@for ($i = 0; $i < 4; $i++)
		<?php
		 $offset = $i + $dayOffset;
		 $day = \Carbon\Carbon::parse($today)->addDay($offset);
		 ?>
			<section class="day" data-num="{{ $i }}" id="day-position-{{ $i }}" href="/events/day/{{ $day->format('Y-m-d') }}">
			@include('events.dayAjax', ['day' => $day, 'position' => $i ])
			</section>
		@endfor
	
	</div>
	<script type="text/javascript">
		// init app module on document load
		$(function()
		{
			Home.loadDays();
		});
	</script>