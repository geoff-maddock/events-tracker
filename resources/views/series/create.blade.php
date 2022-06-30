@extends('app')

@section('title', 'Series Add')

@section('content')

<h1 class="display-crumbs text-primary">Add a New Event Series</h1>

	{!! Form::open(['route' => 'series.store', 'class' => 'form-container']) !!}

		@include('series.form')

	{!! Form::close() !!}

	{!! link_to_route('series.index', 'Return to list') !!}
@stop
