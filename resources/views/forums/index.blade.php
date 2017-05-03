@extends('app')

@section('title','Forum')

@section('content')

	<h1>Forum
		@include('forums.crumbs')
	</h1>

	<p>
	<a href="{{ url('/forums/all') }}" class="btn btn-info">Show all forums</a>
	<a href="{!! URL::route('forums.index') !!}" class="btn btn-info">Show paginated forums</a>
	<a href="{!! URL::route('forums.create') !!}" class="btn btn-primary">Add a forum</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	@if (isset($forums) && count($forums) > 0)
	<div class="col-lg-12">
		@include('forums.list', ['forums' => $forums])
		{!! $forums->render() !!}

	</div>
	@endif

@stop
 