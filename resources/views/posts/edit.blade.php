@extends('app')

@section('title','Post Edit')

@section('content')

<h1 class="display-6 text-primary">Post . Edit  @include('posts.crumbs', ['slug' => $post->slug ? $post->slug : $post->id])</h1>

	{!! Form::model($post, ['route' => ['posts.update', $post->id], 'method' => 'PATCH']) !!}

		@include('posts.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['posts.destroy', $post->id]) !!}</P>

	<P><a href="{!! URL::route('posts.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
