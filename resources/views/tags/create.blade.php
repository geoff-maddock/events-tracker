@extends('app')

@section('title', 'Tag Add')

@section('content')

<h1 class="display-6 text-primary">Add a New Tag</h1>

{!! Form::open(['route' => 'tags.store']) !!}

	@include('tags.form')

{!! Form::close() !!}

{!! link_to_route('tags.index', 'Return to list') !!}
@stop
