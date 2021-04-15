@extends('app')

@section('title','Groups')

@section('content')

		<h4>Groups 
				@include('groups.crumbs')
		</h4>

		<div id="action-menu" style="margin-bottom: 5px;">
			<a href="{!! URL::route('groups.create') !!}" class="btn btn-primary">Add a group</a>
		</div>


		<!-- NAV / FILTER -->
		<div id="filters-container" class="row">
			<div id="filters-content" class="col-lg-9">
				<a href="#" id="filters" class="btn btn-primary">
					Filters
					<span id="filters-toggle"
						class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
				{!! Form::open(['route' => [$filterRoute ?? 'groups.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
		
				<div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
					style="@if (!$hasFilter) display: none; @endif">
		
					<div class="form-group col-sm-2">
						{!! Form::label('filter_name','Filter By Name') !!}
						{!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
						[
							'class' => 'form-control',
							'name' => 'filters[name]'
						]) !!}
					</div>
		
					<div class="form-group col-sm-2">
						{!! Form::label('filter_label','Filter By Label') !!}
						{!! Form::text('filter_label', (isset($filters['label']) ? $filters['label'] : NULL),
						[
							'class' => 'form-control',
							'name' => 'filters[label]'
						]) !!}
					</div>

					<div class="form-group col-sm-2">
						{!! Form::label('filter_level','Filter By Level') !!}
						{!! Form::text('filter_level', (isset($filters['level']) ? $filters['level'] : NULL),
						[
							'class' => 'form-control',
							'name' => 'filters[level]'
						]) !!}
					</div>
		
					<div class="col-sm-2">
						<div class="btn-group col-sm-1">
							<label></label>
							{!! Form::submit('Apply',
							[
							'class' => 'btn btn-primary btn-sm btn-tb mx-2',
							'id' => 'primary-filter-submit'
							])
							!!}
							{!! Form::close() !!}
							{!! Form::open(['route' => ['groups.reset'], 'method' => 'GET']) !!}
							{!! Form::hidden('redirect', $redirect ?? 'groups.index') !!}
							{!! Form::hidden('key', $key ?? 'internal_group_index') !!}
							{!! Form::submit('Reset',
							[
							'class' => 'btn btn-primary btn-sm btn-tb',
							'id' => 'primary-filter-reset'
							]) !!}
							{!! Form::close() !!}
						</div>
					</div>
				</div>

				
			</div>

			<div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
				<form action="{{ url()->current() }}" method="GET" class="form-inline">
					<div class="form-group">
						<a href="{{ url()->action('GroupsController@rppReset') }}" class="btn btn-primary">
							<span class="glyphicon glyphicon-repeat"></span>
						</a>
						{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
						{!! Form::select('sort', $sortOptions, ($sort ?? 'groups.name'), ['class' =>'form-control
						auto-submit'])
						!!}
						{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' =>'form-control
						auto-submit']) !!}
					</div>
				</form>
			</div>

		<div id="all-groups-list" class="col-md-6">
			{!! $groups->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->render() !!}
			@include('groups.list', ['groups' => $groups])
			{!! $groups->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->render() !!}
		</div>

@stop

@section('footer')
<script>
	$(document).ready(function() {
		$('#filters').click(function() {
			$('#filter-list').toggle();
			if ($('#filters-toggle').hasClass('glyphicon-chevron-down')) {
				$('#filters-toggle').removeClass('glyphicon-chevron-down');
				$('#filters-toggle').addClass('glyphicon-chevron-up');
			} else {
				$('#filters-toggle').removeClass('glyphicon-chevron-up');
				$('#filters-toggle').addClass('glyphicon-chevron-down');
			}
		});
	});
</script>
@endsection