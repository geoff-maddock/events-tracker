@extends('app')

@section('title','Events')

@section('calendar.include')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.6.0/main.min.css' rel='stylesheet' />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.6.0/main.min.js"></script>
@endsection

@section('content')

	<h1 class="display-crumbs text-primary">Events Calendar
		@include('events.crumbs')
	</h1>

@stop

@section('footer')
	<div class="m-2">
		{!! $calendar->calendar() !!}
		{!! $calendar->script() !!}
    </div>
@endsection