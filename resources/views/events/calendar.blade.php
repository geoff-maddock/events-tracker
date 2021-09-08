@extends('app')

@section('title','Events')

@section('content')

	<h1 class="display-6 text-primary">Events Calendar
		@include('events.crumbs')
	</h1>

@stop

@section('footer')
	<div style='margin: 10px;'>
		{!! $calendar->calendar() !!}
		{!! $calendar->script() !!}
    </div>
@endsection