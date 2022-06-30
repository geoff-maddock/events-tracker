@extends('app')

@section('title','Event Calendar')

@section('content')

	<h1 class="display-crumbs text-primary">Events Calendar	@include('events.crumbs')</h1>

		<div id='calendar'></div>
@stop

@section('footer')
	<div class='m-2'>
		<script>

			document.addEventListener('DOMContentLoaded', function() {
			  var calendarEl = document.getElementById('calendar');
			  var calendar = new FullCalendar.Calendar(calendarEl, {
				headerToolbar: { center: 'dayGridMonth,timeGridWeek,timeGridDay' },
				initialView: 'dayGridMonth',
				// directly use JSON with events
				// events: {!! $eventList !!},
				// use an API call with eventSources
				eventSources: [
					{
						url: '/api/tag-calendar-events',
					}
				],
				height: 820,
			  });
			  calendar.render();
			});
	  
		  </script>
    </div>
@endsection