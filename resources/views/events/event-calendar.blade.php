@extends('app')

@section('title','Events')

@section('content')

	<h4>Events Calendar
		@include('events.crumbs')
	</h4>

		<div id='calendar'></div>
@stop

@section('footer')
	<div style='margin: 10px;'>
		<script>

			document.addEventListener('DOMContentLoaded', function() {
			  var calendarEl = document.getElementById('calendar');
			  var calendar = new FullCalendar.Calendar(calendarEl, {
				headerToolbar: { center: 'dayGridMonth, timeGridWeek, timeGridDay' },
				initialView: 'dayGridMonth',
				eventSources: [
					{
						url: '/api/calendar-events',
					}
				],
				height: 820
			  });
			  calendar.render();
			});
	  
		  </script>
    </div>
@endsection