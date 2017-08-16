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
	<div class="btn-group">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			Thread Category <span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<li><a  href="/threads">General</a></li>
			@foreach (App\ThreadCategory::all() as $category)
			<li><a  href="/threads/category/{{ urlencode($category->name) }}">{{ $category->name }}</a></li>
			@endforeach
		</ul>
	</div>
	</p>

	<br style="clear: left;"/>

	<div class="row">
		<div class="col-md-6">
			{!! $threads->appends(['sort_by' => $sortBy, 'rpp' => $rpp])->render() !!}
		</div>
		<div class="col-md-6">
			<ul class="pagination pull-right" style="margin-top: 0px;">
				<li class="disabled"><span class="label label-info">RPP</span></li>
				<li @if ($rpp == 5) class="active" @endif >{!! link_to_route('threads.index', '5', ['rpp' => 5], ['class' => 'item-title']) !!}</li>
				<li @if ($rpp == 10) class="active" @endif >{!! link_to_route('threads.index', '10', ['rpp' => 10], ['class' => 'item-title']) !!}</li>
				<li @if ($rpp == 25) class="active" @endif >{!! link_to_route('threads.index', '25', ['rpp' => 25], ['class' => 'item-title']) !!}</li>
				<li @if ($rpp == 100) class="active" @endif >{!! link_to_route('threads.index', '100', ['rpp' => 100], ['class' => 'item-title']) !!}</li>
			</ul>
		</div>
	</div>

	<div class="row">

	@if (isset($threads) && count($threads) > 0)
	<div class="col-lg-12">
		@include('threads.list', ['threads' => $threads])


	</div>
	@endif

@stop
 