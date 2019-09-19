@extends('app')

@section('title','Blogs')

@section('content')

		<h4>Blogs
				@include('blogs.crumbs')
		</h4>

		<P><a href="{!! URL::route('blogs.create') !!}" class="btn btn-primary">Add a blog</a></P>

		<!-- NAV / FILTER -->
		<div class="row nav">

		</div>

		<div class='col-md-6'>
		@include('blogs.list', ['blogs' => $blogs])
		{!! $blogs->appends(['sort_by' => $sortBy, 'rpp' => $rpp])->render() !!}

		<!-- SET RPP -->

		<ul class="pagination">
			<li class="disabled"><span class="label label-info">RPP</span></li>
			<li @if ($rpp == 5) class="active" @endif >{!! link_to_route('blogs.index', '5', ['rpp' => 5], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 10) class="active" @endif >{!! link_to_route('blogs.index', '10', ['rpp' => 10], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 25) class="active" @endif >{!! link_to_route('blogs.index', '25', ['rpp' => 25], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 100) class="active" @endif >{!! link_to_route('blogs.index', '100', ['rpp' => 100], ['class' => 'item-title']) !!}</li>
		</ul>
		</div>

@stop
