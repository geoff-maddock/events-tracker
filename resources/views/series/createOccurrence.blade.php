@extends('app')

@section('title','Event Series -  Create Occurrence')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
	<h2>{{ $series->name}} </h2>
	<h4>Add Occurrence: {{ $event->name }}</h4>
	
	{!! Form::open(['route' => 'events.store', 'class' => 'form-container']) !!}

		@include('events.form', ['action' => 'createOccurrence'])

	{!! Form::close() !!}

	<P><a href="{!! URL::route('series.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
