@extends('app')

@section('content')

<h1 class="display-6 text-primary">Add a New Category</h1>

{!! Form::open(['route' => 'categories.store']) !!}

	@include('categories.form')

{!! Form::close() !!}

{!! link_to_route('categories.index', 'Return to list') !!}
@stop
