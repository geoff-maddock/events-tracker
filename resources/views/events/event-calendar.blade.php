@extends('app')

@section('title','Event Calendar')

@section('calendar.include')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
@endsection

@section('content')

	<h1 class="display-crumbs text-primary">Events Calendar	@include('events.crumbs')</h1>

		<div id='calendar'></div>
@stop

@section('footer')
	<div class='m-2'>
		<script>

			// check the current viewport size
			function checkViewport() {
				if (window.innerWidth < 768) {
					return 'timeGridDay';
				} else if (window.innerWidth < 1024) {
					return 'timeGridWeek';
				} else {
					return 'dayGridMonth';
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
			  var calendarEl = document.getElementById('calendar');
			  var calendar = new FullCalendar.Calendar(calendarEl, {
				headerToolbar: { center: 'dayGridMonth,timeGridWeek,timeGridDay' },
				initialView: checkViewport(),
				// directly use JSON with events
				events: {!! $eventList !!},
				// use an API call with eventSources
				// eventSources: [
				// 	{
				// 		url: '/api/calendar-events',
				// 	}
				// ],
				height: 820,
				initialDate: '{{ $initialDate }}',
			  });
			  calendar.render();
			});
	  
		  </script>
    </div>
@endsection