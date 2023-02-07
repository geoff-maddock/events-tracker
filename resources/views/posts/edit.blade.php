@extends('app')

@section('title','Post Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h1 class="display-crumbs text-primary">Post . Edit  @include('posts.crumbs', ['slug' => $post->slug ? $post->slug : $post->id])</h1>

	{!! Form::model($post, ['route' => ['posts.update', $post->id], 'method' => 'PATCH']) !!}

		@include('posts.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['posts.destroy', $post->id]) !!}</P>

	<P><a href="{!! URL::route('posts.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
