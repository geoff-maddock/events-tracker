@extends('app')

@section('content')

	<h4>Event Series</h4>

	<p>
		<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
		<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show current series</a>
		<a href="{!! URL::route('series.cancelled') !!}" class="btn btn-info">Show cancelled series</a>
	</p>

	@include('series.list', ['series' => $series])

@stop
 
