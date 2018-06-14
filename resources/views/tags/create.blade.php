@extends('app')

@section('title', 'Tag Add')

@section('content')
<script src="{{ asset('/js/facebook-sdk.js') }}"></script>

	<h1>Add a New Tag</h1>

	{!! Form::open(['route' => 'tags.store']) !!}

		@include('tags.form')

	{!! Form::close() !!}

	{!! link_to_route('tags.index', 'Return to list') !!}
@stop
@section('scripts.footer')
<script src="{{ asset('/js/facebook-event.js') }}"></script>
@stop