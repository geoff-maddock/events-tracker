@extends('app')

@section('title', 'Events - Grid')

@section('content')

<h4>Events @include('events.crumbs')</h4>

<div id="action-menu" style="margin-bottom: 5px;">
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show event index</a>
	<a href="{!! URL::route('calendar') !!}" class="btn btn-info">Show calendar</a>
	<a href="{!! URL::route('events.week') !!}" class="btn btn-info">Show week's events</a>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	<a href="{!! URL::route('events.export') !!}" class="btn btn-primary" target="_blank">Export</a>
</div>

<div id="filters-container" class="row">
	<div id="filters-content" class="col-lg-9">

		<a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle"
				class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
		{!! Form::open(['route' => ['events.grid'], 'method' => 'POST']) !!}

		<div id="filter-list" @if (!$hasFilter)style="display: none" @endif class="row">

			<div class="form-group col-sm-2">
				{!! Form::label('filter_name','Filter By Name') !!}
				{!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
				[
					'class' => 'form-control',
					'name' => 'filters[name]'
				]) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_venue', 'Venue', array('width' => '100%')) !!}<br>
				{!! Form::select('filter_venue', $venueOptions, (isset($filters['venue']) ? $filters['venue'] :
				NULL),
				[
				'data-theme' => 'bootstrap',
				'data-width' => '100%',
				'class' =>'form-control select2',
				'data-placeholder' => 'Select a venue',
				'name' => 'filters[venue]'
				])
				!!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_tag', 'Tag') !!}
				{!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
				[
				'data-theme' => 'bootstrap',
				'data-width' => '100%',
				'class' =>'form-control select2',
				'data-placeholder' => 'Select a tag',
				'name' => 'filters[tag]'
				]) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_related','Related Entity') !!}
				{!! Form::select('filter_related', $relatedOptions, (isset($filters['related'])
				? $filters['related'] : NULL),
				[
				'data-theme' => 'bootstrap',
				'data-width' => '100%',
				'class' => 'form-control select2',
				'data-placeholder' => 'Select an entity',
				'name' => 'filters[related]'
				]) !!}
			</div>


			<div class="form-group col-sm-2">
				{!! Form::label('filter_event_type','Type') !!}
				{!! Form::select('filter_event_type', $eventTypeOptions, (isset($filters['event_type'])
				? $filters['event_type'] : NULL),
				[
				'data-theme' => 'bootstrap',
				'data-width' => '100%',
				'class' => 'form-control select2',
				'data-placeholder' => 'Select a type',
				'name' => 'filters[event_type]'
				]) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_start_at','Start At') !!}

				<div class="d-flex">
					{!! Form::label('start', 'From:', ['style' => 'width: 35px;']) !!}
					{!! Form::date('start_at',
					(isset($filters['start_at']['start']) ? $filters['start_at']['start'] : NULL),
					[
					'style' => 'padding: 8px 16px;',
					'name' => 'filters[start_at][start]'
					])
					!!}
				</div>
				<div class="d-flex">
					{!! Form::label('end','To:', ['style' => 'width: 35px;']) !!}
					{!! Form::date('start_at',
					(isset($filters['start_at']['end']) ? $filters['start_at']['end'] : NULL),
					[
					'style' => 'padding: 8px 16px;',
					'name' => 'filters[start_at][end]'
					])
					!!}
				</div>
			</div>


			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
					<label></label>
					<label></label>
					{!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
					'primary-filter-submit']) !!}
					{!! Form::close() !!}
					{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}
					{!! Form::hidden('redirect','events.grid') !!}
					{!! Form::hidden('key','internal_event_grid') !!}
					{!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
					'primary-filter-reset']) !!}
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</div>
	<div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
		<form action="{{ url()->current() }}" method="GET" class="form-inline">
			<div class="form-group">
				<a href="{{ url()->action('EventsController@rppReset') }}" class="btn btn-primary">
					<span class="glyphicon glyphicon-repeat"></span>
				</a>
				{!! Form::select('limit', $limitOptions, ($limit ?? 24), ['class' =>'form-control auto-submit']) !!}
				{!! Form::select('sort', $sortOptions, ($sort ?? 'events.start_at'), ['class' =>'form-control
				auto-submit'])
				!!}
				{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' =>'form-control
				auto-submit']) !!}
			</div>
		</form>
	</div>
	<!-- END: FILTERS -->
</div>

<br style="clear: left;" />

<div class="row">

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-12">
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Events</h3>
				</div>

				<div class="panel-body">
					<div style="display: grid; grid-template-columns:  repeat(auto-fill, minmax(200px, 1fr));">
						@foreach ($events as $event)
						@include('events.cell', ['event' => $event])
						@endforeach
					</div>
					{!! $events->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif

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