@extends('app')

@section('content')

	<h4>Event Series</h4>

	<p>
		<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
		<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show current series</a>
		<a href="{!! URL::route('series.cancelled') !!}" class="btn btn-info">Show cancelled series</a>
	</p>

	<!-- NAV / FILTER -->
	<div class="row" class="tab-content filters-content">
		<div class="col-sm-12">
		{!! Form::open(['route' => ['series.filter'], 'method' => 'GET']) !!}

		<!-- BEGIN: FILTERS -->
			@if ($hasFilter)

				<div class="form-group col-sm-2">

					{!! Form::label('filter_name','Filter By Name') !!}

					{!! Form::text('filter_name', (isset($name) ? $name : NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-1">

                    {!! Form::label('filter_occurrence_type','Occurrence Type') !!}
                    <?php $types = [''=>''] + App\OccurrenceType::orderBy('name','ASC')->pluck('name','name')->all();?>
                    {!! Form::select('filter_occurrence_type', $types, (isset($type) ? $type : NULL), ['class' =>'form-control']) !!}
                </div>

                <div class="form-group col-sm-1">
                    {!! Form::label('filter_occurrence_week','Week') !!}
                    <?php $weeks = [''=>''] + App\OccurrenceWeek::orderBy('name','ASC')->pluck('name','name')->all();?>
                    {!! Form::select('filter_occurrence_week', $weeks, (isset($week) ? $week : NULL), ['class' =>'form-control']) !!}
                </div>

                <div class="form-group col-sm-1">
                    {!! Form::label('filter_occurrence_day','Day') !!}
                    <?php $days = [''=>''] + App\OccurrenceDay::orderBy('name','ASC')->pluck('name','name')->all();?>
                    {!! Form::select('filter_occurrence_day', $days, (isset($day) ? $day : NULL), ['class' =>'form-control']) !!}
                </div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_tag','Tag') !!}
                    <?php $tags =  [''=>'&nbsp;'] + App\Tag::orderBy('name','ASC')->lists('name', 'name')->all();?>
					{!! Form::select('filter_tag', $tags, (isset($tag) ? $tag : NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-1">
					{!! Form::label('filter_rpp','RPP') !!}
                    <?php $rpp_options =  [''=>'&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
					{!! Form::select('filter_rpp', $rpp_options, (isset($rpp) ? $rpp : NULL), ['class' =>'form-control']) !!}
				</div>
			@endif

			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
					{!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}

					{!! Form::close() !!}

					{!! Form::open(['route' => ['series.reset'], 'method' => 'GET']) !!}

					{!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}

					{!! Form::close() !!}
				</div>
			</div>

		</div>
		<!-- END: FILTERS -->
	</div>


	@include('series.list', ['series' => $series])

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
