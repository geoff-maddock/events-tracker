@extends('app')

@section('title', 'Events - Grid')

@section('content')

	<h4>Events @include('events.crumbs')</h4>

    <div id="action-menu" style="margin-bottom: 5px;">
        <a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show event index</a>
        <a href="{!! URL::route('calendar') !!}" class="btn btn-info mt-2 mr-2">Show calendar</a>
        <a href="{!! URL::route('events.week') !!}" class="btn btn-info">Show week's events</a>
        <a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>
        <a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	</div>

    <div id="filters-container" class="row">
        <div id="filters-content" class="col-lg-9">

			<a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle" class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
			{!! Form::open(['route' => ['events.grid'], 'method' => 'GET']) !!}

            <div id="filter-list" @if (!$hasFilter)style="display: none"@endif class="row">

				<div class="form-group col-sm-3">
					{!! Form::label('filter_name','Filter By Name') !!}
					{!! Form::text('filter_name', (isset($filters['filter_name']) ? $filters['filter_name'] : NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_venue','Filter By Venue') !!}
                    <?php $venues = [''=>''] + App\Entity::getVenues()->pluck('name','name')->all();?>
					{!! Form::select('filter_venue', $venues, (isset($filters['filter_venue']) ? $filters['filter_venue'] : NULL), ['data-theme' => 'bootstrap', 'data-width' => '100%','class' =>'form-control select2', 'data-placeholder' => 'Select a venue']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_tag','Filter By Tag') !!}
                    <?php $tags =  [''=>'&nbsp;'] + App\Tag::orderBy('name','ASC')->pluck('name', 'name')->all();?>
					{!! Form::select('filter_tag', $tags, (isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL), ['data-theme' => 'bootstrap', 'data-width' => '100%','class' =>'form-control select2', 'data-placeholder' => 'Select a tag']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_related','Filter By Related') !!}
                    <?php $related = [''=>''] + App\Entity::orderBy('name','ASC')->pluck('name','name')->all();?>
					{!! Form::select('filter_related', $related, (isset($filters['filter_related']) ? $filters['filter_related'] : NULL), ['data-theme' => 'bootstrap', 'data-width' => '100%','class' =>'form-control select2', 'data-placeholder' => 'Select an entity']) !!}
				</div>

                <div class="col-sm-2">
                    <div class="btn-group col-sm-1">
                        <label></label>
						{!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}
						{!! Form::close() !!}
						{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}
						{!! Form::hidden('redirect','events.grid') !!}
						{!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}
						{!! Form::close() !!}
					</div>
				</div>
			</div>
		</div>
        <div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
            <form action="{{ url()->current() }}" method="GET" class="form-inline">
                <div class="form-group">
                    <?php $rpp_options =  [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
                    <?php $sort_by_options = ['name' => 'Name','start_at' => 'Start At', 'event_type_id' => 'Event Type', 'updated_at' => 'Updated At']; ?>
                    <?php $sort_order_options = ['asc' => 'asc', 'desc' => 'desc']; ?>
                    {!! Form::select('rpp', $rpp_options, ($rpp ?? 10), ['class' =>'form-control auto-submit']) !!}
                    {!! Form::select('sortBy', $sort_by_options, ($sortBy ?? 'name'), ['class' =>'form-control auto-submit']) !!}
                    {!! Form::select('sortOrder', $sort_order_options, ($sortOrder ?? 'asc'), ['class' =>'form-control auto-submit']) !!}
                </div>
            </form>
        </div>
		<!-- END: FILTERS -->
	</div>

	<br style="clear: left;"/>

	<div class="row">

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-12">
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Events</h3>
				</div>

				<div class="panel-body">
					<div style="display: grid; grid-template-columns: repeat(8, 1fr);  grid-auto-rows: minmax(100px, auto);">
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
            $('#filters').click(function () {
                $('#filter-list').toggle();
                if ($('#filters-toggle').hasClass('glyphicon-chevron-down'))
                {
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
