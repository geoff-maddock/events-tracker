@extends('app')

@section('title','Entity Types')

@section('content')

		<h4>Entity Types
				@include('entityTypes.crumbs')
		</h4>

		<P><a href="{!! URL::route('entity-types.create') !!}" class="btn btn-primary">Add an entity type</a></P>

		<!-- NAV / FILTER -->
		<div class="row nav">

		</div>

		<div class='col-md-6'>
		@include('entityTypes.list', ['entityTypes' => $entityTypes])

		@if (!$entityTypes->isEmpty())
            {!! $entityTypes->render() !!}
        @endif
		<!-- SET RPP -->

		<ul class="pagination">
			<li class="disabled"><span class="label label-info">RPP</span></li>
			<li @if ($rpp == 5) class="active" @endif >{!! link_to_route('entity-types.index', '5', ['rpp' => 5], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 10) class="active" @endif >{!! link_to_route('entity-types.index', '10', ['rpp' => 10], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 25) class="active" @endif >{!! link_to_route('entity-types.index', '25', ['rpp' => 25], ['class' => 'item-title']) !!}</li>
			<li @if ($rpp == 100) class="active" @endif >{!! link_to_route('entity-types.index', '100', ['rpp' => 100], ['class' => 'item-title']) !!}</li>
		</ul>
		</div>

@stop
