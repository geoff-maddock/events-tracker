@extends('app')

@section('content')

	<i>{{ $series->start_at->format('l F jS Y \\at h:i A') }} </i> 
	<h2>{{ $series->name }}</h2>

	{!! Form::model($series, ['route' => ['series.update', $series->id], 'method' => 'PATCH']) !!}

		@include('series.form', ['action' => 'update'])

	{!! Form::close() !!}
 
	{!! delete_form(['series.destroy', $series->id]) !!}

	{!! link_to_route('series.index','Return to list') !!}
@stop
