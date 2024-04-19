@extends('app')

@section('title','Events')

@section('calendar.include')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
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