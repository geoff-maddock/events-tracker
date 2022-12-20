@extends('app')

@section('title','Blog Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h1 class="display-6 text-primary">Blog . Edit . {{ $blog->name }}</h1>

	{!! Form::model($blog, ['route' => ['blogs.update', $blog->slug], 'method' => 'PATCH']) !!}

		@include('blogs.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['blogs.destroy', $blog->slug]) !!}

	{!! link_to_route('blogs.index','Return to list') !!}
@stop
