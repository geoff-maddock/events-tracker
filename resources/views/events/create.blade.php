@extends('app')

@section('title', 'Event Add')

@section('content')
<script src="{{ asset('/js/facebook-sdk.js') }}"></script>
<script async defer src="https://connect.facebook.net/en_US/sdk.js"></script>

	<h4>Add a New Event</h4>

	{!! Form::open(['route' => 'events.store']) !!}

		@include('events.form')

	{!! Form::close() !!}

	{!! link_to_route('events.index', 'Return to list') !!}
@stop
@section('scripts.footer')
<script src="{{ asset('/js/facebook-event.js') }}"></script>
@stop
