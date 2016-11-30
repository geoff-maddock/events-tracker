@extends('app')

@section('content')

	<h1>Event Series</h1>

	<p><a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a></p>

	@include('series.list', ['series' => $series])

@stop
 
