@extends('app')

@section('title','Events')

@section('content')

	<h1 class="display-6 text-primary">Events Calendar
		@include('events.crumbs')
	</h1>

@stop

@section('footer')
	<div class="m-2">
		{!! $calendar->calendar() !!}
		{!! $calendar->script() !!}
    </div>
@endsection