@extends('app')

@section('title','Permissions')

@section('content')

		<h4>Permissions
				@include('permissions.crumbs')
		</h4>

		<P><a href="{!! URL::route('permissions.create') !!}" class="btn btn-primary">Add a permission</a></P>

		<!-- NAV / FILTER -->
		<div class="row nav">

		</div>

		<div class='col-md-6'>
		@include('permissions.list', ['permissions' => $permissions])
		{!! $permissions->appends(['sort_by' => $sortBy, 'rpp' => $rpp])->render() !!}

		<!-- SET RPP -->

		<ul class="pagination">
			<li class="disabled"><span class="label label-info">RPP</span></li>
			<li @if ($rpp == 5) class="active" @endif >{!! link_to_route('permissions.index', '5', ['rpp' => 5], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 10) class="active" @endif >{!! link_to_route('permissions.index', '10', ['rpp' => 10], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 25) class="active" @endif >{!! link_to_route('permissions.index', '25', ['rpp' => 25], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 100) class="active" @endif >{!! link_to_route('permissions.index', '100', ['rpp' => 100], ['class' => 'item-title']) !!}</li>
		</ul>
		</div>

@stop
