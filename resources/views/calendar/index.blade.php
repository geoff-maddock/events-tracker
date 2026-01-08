@extends('layouts.app-tw')

@section('title','Events Calendar')

@section('content')

		<div id='calendar'></div>

		<P><a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a></P>
		<p><a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a></p>

@stop

@section('footer')
	<script>
		$(document).ready(function() {

		    // page is now ready, initialize the calendar...

		    $('#calendar').fullCalendar({
		        // put your options and callbacks here
		        height: 800
		    })

		});
	</script>
@endsection