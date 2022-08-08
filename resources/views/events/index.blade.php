@extends('app')

@section('title')
Events @include('events.title-crumbs')
@endsection

@if (isset($past_events) && count($past_events) > 0)
@php
	$first = $past_events[0];
	if ($primary = $first->getPrimaryPhoto()) {
		$ogImage = Storage::disk('external')->url($primary->getStorageThumbnail());
	}
@endphp
@endif 

@if (isset($future_events) && count($future_events) > 0)
@php
	$first = $future_events[0];
	if ($primary = $first->getPrimaryPhoto()) {
		$ogImage = Storage::disk('external')->url($primary->getStorageThumbnail());
	}
@endphp
@endif 

@if (isset($ogImage))
@section('og-image')
{!! url('/').$ogImage !!}
@endsection
@endif

@section('content')

<h1 class="display-crumbs text-primary">Events @include('events.crumbs')</h1>

<div id="action-menu" class="mb-2">
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info my-1">Show event index</a>
	<a href="{!! URL::route('calendar') !!}" class="btn btn-info my-1">Show calendar</a>
	<a href="{!! URL::route('events.week') !!}" class="btn btn-info my-1">Show week's events</a>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary my-1">Add an event</a>
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary my-1">Add an event series</a>
	@if (isset($slug) && $slug == 'Attending')
	<a href="{!! URL::route('events.export.attending') !!}" class="btn btn-primary my-1" target="_blank">Export</a>
    @elseif (!isset($slug))
	<a href="{!! URL::route('events.export') !!}" class="btn btn-primary my-1" target="_blank">Export</a>
	@endif
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
		{!! Form::open(['route' => [$filterRoute ?? 'events.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
		<div class="row">
			<div class="col-sm">
				{!! Form::label('filter_name','Name') !!}
				{!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
				[
					'class' => 'form-control form-background',
					'name' => 'filters[name]',
					'data-theme' => 'bootstrap-5',
				]) !!}
			</div>

			<div class="col-sm">
				{!! Form::label('filter_venue', 'Venue', array('width' => '100%')) !!}<br>
				{!! Form::select('filter_venue', $venueOptions, (isset($filters['venue']) ? $filters['venue'] :	NULL),
				[
					'data-theme' => 'bootstrap-5',
					'data-width' => '100%',
					'class' => 'form-select select2 form-background',
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
					'class' =>'form-select select2 form-background',
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
				{!! Form::label('filter_event_type','Type') !!}
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
						'name' => 'filters[start_at][start]',
						'data-theme' => 'bootstrap-5',
						'class' => 'form-control form-background  date-input'
					])
					!!}
				</div>
				<div class="d-flex">
					{!! Form::label('end','To:', ['style' => 'width: 3rem;']) !!}
					{!! Form::date('start_at',
					(isset($filters['start_at']['end']) ? $filters['start_at']['end'] : NULL),
					[
						'name' => 'filters[start_at][end]',
						'data-theme' => 'bootstrap-5',
						'class' => 'form-control form-background my-2  date-input'
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
					'class' => 'btn btn-primary btn-sm btn-tb me-2 my-2',
					'id' => 'primary-filter-submit'
					])
					!!}
					{!! Form::close() !!}
					{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}
					{!! Form::hidden('redirect', $redirect ?? 'events.index') !!}
					{!! Form::hidden('key', $key ?? 'internal_event_index') !!}
					{!! Form::submit('Reset',
					[
						'class' => 'btn btn-primary btn-sm btn-tb me-2 my-2',
						'id' => 'primary-filter-reset'
					]) !!}
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</div>
</div>
	<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right my-2">
		<form action="{{ url()->current() }}" method="GET" class="form-inline">
			<div class="form-group row gx-1 justify-content-end">
				<div class="col-auto">
					<a href="{{ url()->action('EventsController@rppReset') }}?key={!! $key  ?? '' !!}" class="btn btn-primary" alt="Reset" aria-label="Reset">
						<i class="bi bi-arrow-clockwise"></i>
					</a>
				</div>
				<div class="col-auto">
					{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-background form-select auto-submit']) !!}
				</div>
				<div class="col-auto">
					{!! Form::select('sort', $sortOptions, ($sort ?? 'events.start_at'), ['class' => 'form-background form-select auto-submit']) !!}
				</div>
				<div class="col-auto">
					{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' => 'form-background form-select auto-submit']) !!}
				</div>
			</div>
		</form>
	</div>
</div>

<div id="list-container" class="row">

	@if (isset($events))
		@if (count($events) > 0)
		<div id="all-events-list" class="col-lg-6">
			<div class="bs-component">
				<div class="panel panel-info">

					<div class="panel-body">
						{!! $events->onEachSide(2)->links() !!}
						@include('events.list', ['events' => $events])
						{!! $events->onEachSide(2)->links() !!}
					</div>

				</div>
			</div>
		</div>
		@else 
		<div><small class="text-muted">No matching events.</small></div>
		@endif
	@endif

	@if (isset($past_events) && count($past_events) > 0)
	<div id="past-events-list" class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title"><a href="{{ url('/events/past') }}">Past Events</a></h3>
				</div>

				<div class="panel-body">
					{!! $past_events->onEachSide(2)->links() !!}
					@include('events.list', ['events' => $past_events])
					{!! $past_events->onEachSide(2)->links() !!}
				</div>

			</div>
		</div>
	</div>
	@endif

	@if (isset($future_events) && count($future_events) > 0)
	<div id="future-events-list" class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title"><a href="{{ url('/events/future') }}">Future Events</a></h3>
				</div>

				<div class="panel-body">
					{!! $future_events->onEachSide(2)->links() !!}
					@include('events.list', ['events' => $future_events])
					{!! $future_events->onEachSide(2)->links() !!}
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