@extends('app')

@section('title','Forum')

@section('content')

	<h1>Forum
		@include('threads.crumbs')
	</h1>

	<p>
	<a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
	<a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paginated threads</a>
	<a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	@if (isset($threads) && count($threads) > 0)
	<div class="col-lg-12">
		@include('threads.list', ['threads' => $threads])
		{!! $threads->render() !!}

	</div>
	@endif

@stop
 