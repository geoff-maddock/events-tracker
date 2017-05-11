@extends('app')

@section('title','Events')

@section('content')

	<h4>Events Calendar
		@include('events.crumbs')
	</h4>

@stop

@section('footer')
	<div style='margin: 10px;'>
		{!! $calendar->calendar() !!}
		{!! $calendar->script() !!}
    </div>
@endsection