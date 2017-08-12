@extends('app')

@section('title','Events')

@section('content')

	<h4>Events
		@include('events.crumbs')
	</h4>

	<div class="col-md-6">
	<a href="{{ url('/events/all') }}" class="btn btn-info">Show all events</a>
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show paginated events</a>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	</div>

		<!-- NAV / FILTER -->
		<div class="row" class="tab-content filters-content">
			<div class="col-sm-6">
			{!! Form::open(['route' => ['events.filter'], 'method' => 'GET']) !!}

			<!-- BEGIN: FILTERS -->
				@if ($hasFilter)

					<div class="form-group col-sm-4">

						{!! Form::label('filter_name','Filter By Name') !!}

						{!! Form::text('filter_name', (isset($name) ? $name : NULL), ['class' =>'form-control']) !!}
					</div>

					<div class="form-group col-sm-2">

						{!! Form::label('filter_venue','Filter By Venue') !!}
                        <?php $venues = [''=>''] + App\Entity::getVenues()->pluck('name','name')->all();?>
						{!! Form::select('filter_venue', $venues, (isset($venue) ? $venue : NULL), ['class' =>'form-control']) !!}
					</div>

					<div class="form-group col-sm-2">
						{!! Form::label('filter_tag','Filter By Tag') !!}
                        <?php $tags =  [''=>'&nbsp;'] + App\Tag::orderBy('name','ASC')->lists('name', 'name')->all();?>
						{!! Form::select('filter_tag', $tags, (isset($tag) ? $tag : NULL), ['class' =>'form-control']) !!}
					</div>

					<div class="form-group col-sm-2">
						{!! Form::label('filter_rpp','RPP') !!}
                        <?php $rpp_options =  [''=>'&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
						{!! Form::select('filter_rpp', $rpp_options, (isset($rpp) ? $rpp : NULL), ['class' =>'form-control']) !!}
					</div>
				@endif

				<div class="col-sm-2">
					<div class="btn-group col-sm-1">
						{!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}

						{!! Form::close() !!}

						{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}

						{!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}

						{!! Form::close() !!}
					</div>
				</div>

			</div>
			<!-- END: FILTERS -->
		</div>


	<br style="clear: left;"/>

	<div class="row">

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Events</h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $events])
				{!! $events->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif

	@if (isset($past_events) && count($past_events) > 0)
	<div class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title"><a href="{{ url('/events/past') }}">Past Events</a></h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $past_events])
				{!! $past_events->appends(['sort_by' => $sortBy,
									'rpp' => $rpp,
									'filter_venue' => isset($filter_venue) ? $filter_venue : NULL,
									'filter_tag' => isset($filter_tag) ? $filter_tag : NULL,
									'filter_name' => isset($filter_name) ? $filter_name : NULL,
			])->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif
	
	@if (isset($future_events) && count($future_events) > 0)	
	<div class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">

			
				<div class="panel-heading">
					<h3 class="panel-title"><a href="{{ url('/events/future') }}">Future Events</a></h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $future_events])
				{!! $future_events->appends(['sort_by' => $sortBy,
									'rpp' => $rpp,
									'filter_venue' => isset($filter_venue) ? $filter_venue : NULL,
									'filter_tag' => isset($filter_tag) ? $filter_tag : NULL,
									'filter_name' => isset($filter_name) ? $filter_name : NULL,
			])->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif
	</div>

@stop


@section('footer')
	<script>
        // javascript to enable the select2 for the tag and entity list
        $('#filter_tag').select2(
            {

                placeholder: 'Choose a tag',
                tags: true,
            });

	</script>
@endsection