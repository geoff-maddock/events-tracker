@extends('app')

@section('title', 'Forum Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

	<h4>Add a New Forum</h4>

	{!! Form::open(['route' => 'forums.store']) !!}

		@include('forums.form')

	{!! Form::close() !!}

	{!! link_to_route('forums.index', 'Return to list') !!}
@stop
