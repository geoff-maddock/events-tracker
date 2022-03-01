@extends('app')

@section('title', 'Events - Grid')

@section('content')

<h1 class="display-6 text-primary">Events @include('events.crumbs')</h4>

<div id="action-menu" class="mb-2">
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show event index</a>
	<a href="{!! URL::route('calendar') !!}" class="btn btn-info">Show calendar</a>
	<a href="{!! URL::route('events.week') !!}" class="btn btn-info">Show week's events</a>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	<a href="{!! URL::route('events.export') !!}" class="btn btn-primary" target="_blank">Export</a>
</div>

<div id="filters-container" class="row">
	<div id="filters-content" class="col-xl-9">
		<a href="#" id="filters" class="btn btn-primary">
			Filters 
			<span id="filters-toggle" class="@if (!$hasFilter) filter-closed @else filter-open @endif">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16">
				<path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
				</svg>
			</span>
		</a>
		{!! Form::open(['route' => ['events.grid'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
		<div class="row">
			<div class="col-sm">
				{!! Form::label('filter_name','Name') !!}
				{!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
				[
					'class' => 'form-control form-background',
					'name' => 'filters[name]'
				]) !!}
			</div>

			<div class="col-sm">
				{!! Form::label('filter_venue', 'Venue', array('width' => '100%')) !!}<br>
				{!! Form::select('filter_venue', $venueOptions, (isset($filters['venue']) ? $filters['venue'] :
				NULL),
				[
					'data-theme' => 'bootstrap-5',
					'data-width' => '100%',
					'class' =>'form-control select2 form-background',
					'data-placeholder' => 'Select a venue',
					'name' => 'filters[venue]'
				])
				!!}
			</div>

			<div class="col-sm">
				{!! Form::label('filter_tag', 'Tag') !!}
				{!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
				[
					'data-theme' => 'bootstrap-5',
					'data-width' => '100%',
					'class' =>'form-control select2 form-background',
					'data-placeholder' => 'Select a tag',
					'name' => 'filters[tag]'
				]) !!}
			</div>

			<div class="col-sm">
				{!! Form::label('filter_related','Related Entity') !!}
				{!! Form::select('filter_related', $relatedOptions, (isset($filters['related'])
				? $filters['related'] : NULL),
				[
					'data-theme' => 'bootstrap-5',
					'data-width' => '100%',
					'class' => 'form-control select2 form-background',
					'data-placeholder' => 'Select an entity',
					'name' => 'filters[related]'
				]) !!}
			</div>

			<div class="col-sm">
				{!! Form::label('filter_event_type', 'Type') !!}
				{!! Form::select('filter_event_type', $eventTypeOptions, (isset($filters['event_type'])
				? $filters['event_type'] : NULL),
				[
					'data-theme' => 'bootstrap-5',
					'data-width' => '100%',
					'class' => 'form-control select2 form-background',
					'data-placeholder' => 'Select a type',
					'name' => 'filters[event_type]'
				]) !!}
			</div>

			<div class="col-sm">
				{!! Form::label('filter_start_at','Start At') !!}

				<div class="d-flex">
					{!! Form::label('start', 'From:', ['style' => 'width: 3rem;']) !!}
					{!! Form::date('start_at',
					(isset($filters['start_at']['start']) ? $filters['start_at']['start'] : NULL),
					[
					'style' => 'padding: 8px 16px;',
					'name' => 'filters[start_at][start]',
					'class' => 'form-control form-background',
					])
					!!}
				</div>
				<div class="d-flex">
					{!! Form::label('end','To:', ['style' => 'width: 3rem;']) !!}
					{!! Form::date('start_at',
					(isset($filters['start_at']['end']) ? $filters['start_at']['end'] : NULL),
					[
					'style' => 'padding: 8px 16px;',
					'name' => 'filters[start_at][end]',
					'class' => 'form-control form-background my-2',
					])
					!!}
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
					<label></label>
					{!! Form::submit('Apply',
					[
						'class' => 'btn btn-primary btn-sm btn-tb me-2',
						'id' => 'primary-filter-submit'
					])
					!!}
					{!! Form::close() !!}
					{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}
					{!! Form::hidden('redirect', $redirect ?? 'events.grid') !!}
					{!! Form::hidden('key',  $key ?? 'internal_event_grid') !!}
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
</div>
	<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right">
		<form action="{{ url()->current() }}" method="GET" class="form-inline">
			<div class="form-group row gx-1 justify-content-end">
				<div class="col-auto">
					<a href="{{ url()->action('EventsController@rppReset') }}" class="btn btn-primary">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
							<path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
							<path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
						</svg>
					</a>
				</div>
				<div class="col-auto">
					{!! Form::select('limit', $limitOptions, ($limit ?? 24), ['class' =>'form-background form-select auto-submit']) !!}
				</div>
				<div class="col-auto">
					{!! Form::select('sort', $sortOptions, ($sort ?? 'events.start_at'), ['class' =>'form-background form-select auto-submit'])!!}
				</div>
				<div class="col-auto">
					{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' =>'form-background form-select auto-submit']) !!}
				</div>
			</div>
		</form>
	</div>
</div>

<div id="grid-container" class="row">

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-12">
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-body">
					<div class="photo-grid">
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
@include('partials.filter-js')
@endsection