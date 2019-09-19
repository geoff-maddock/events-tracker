@extends('app')

@section('title','Menus')

@section('content')

		<h4>Menus
				@include('menus.crumbs')
		</h4>

		<P><a href="{!! URL::route('menus.create') !!}" class="btn btn-primary">Add a menu</a></P>

		<!-- NAV / FILTER -->
		<div class="row nav">

		</div>

		<div class='col-md-6'>
		@include('menus.list', ['menus' => $menus])
		{!! $menus->appends(['sort_by' => $sortBy, 'rpp' => $rpp])->render() !!}

		<!-- SET RPP -->

		<ul class="pagination">
			<li class="disabled"><span class="label label-info">RPP</span></li>
			<li @if ($rpp == 5) class="active" @endif >{!! link_to_route('menus.index', '5', ['rpp' => 5], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 10) class="active" @endif >{!! link_to_route('menus.index', '10', ['rpp' => 10], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 25) class="active" @endif >{!! link_to_route('menus.index', '25', ['rpp' => 25], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 100) class="active" @endif >{!! link_to_route('menus.index', '100', ['rpp' => 100], ['class' => 'item-title']) !!}</li>
		</ul>
		</div>

@stop
