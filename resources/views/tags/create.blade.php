@extends('app')

@section('title', 'Keyword Tag Add')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<h1 class="display-crumbs text-primary">Add a New Keyword Tag</h1>

{!! Form::open(['route' => 'tags.store']) !!}

	@include('tags.form')

{!! Form::close() !!}

{!! link_to_route('tags.index', 'Return to list') !!}
@stop
