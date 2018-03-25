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
		<div id="filters-container" class="col-sm-12">

			<a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle" class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>

			{!! Form::open(['route' => ['series.filter'], 'method' => 'GET']) !!}

			<div id="filter-list" @if (!$hasFilter)style="display: none"@endif >
			<!-- BEGIN: FILTERS -->

				<div class="form-group col-sm-2">

					{!! Form::label('filter_name','Filter By Name') !!}

					{!! Form::text('filter_name', (isset($filters['filter_name']) ? $filters['filter_name'] : NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-2">

                    {!! Form::label('filter_occurrence_type','Occurrence Type') !!}
                    <?php $types = [''=>''] + App\OccurrenceType::orderBy('name','ASC')->pluck('name','name')->all();?>
                    {!! Form::select('filter_occurrence_type', $types, (isset($filters['filter_type']) ? $filters['filter_type'] : NULL), ['class' =>'form-control']) !!}
                </div>

                <div class="form-group col-sm-2">
                    {!! Form::label('filter_occurrence_week','Week') !!}
                    <?php $weeks = [''=>''] + App\OccurrenceWeek::orderBy('id','ASC')->pluck('name','name')->all();?>
                    {!! Form::select('filter_occurrence_week', $weeks, (isset($filters['filter_week']) ? $filters['filter_week'] : NULL), ['class' =>'form-control']) !!}
                </div>

                <div class="form-group col-sm-2">
                    {!! Form::label('filter_occurrence_day','Day') !!}
                    <?php $days = [''=>''] + App\OccurrenceDay::orderBy('id','ASC')->pluck('name','name')->all();?>
                    {!! Form::select('filter_occurrence_day', $days, (isset($filters['filter_day']) ? $filters['filter_day'] : NULL), ['class' =>'form-control']) !!}
                </div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_tag','Tag') !!}
                    <?php $tags =  [''=>'&nbsp;'] + App\Tag::orderBy('name','ASC')->pluck('name', 'name')->all();?>
					{!! Form::select('filter_tag', $tags, (isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL), ['data-width' => '100%', 'class' =>'form-control select2', 'data-placeholder' => 'Select a tag']) !!}
				</div>

				<div class="form-group col-sm-1">
					{!! Form::label('filter_rpp','RPP') !!}
                    <?php $rpp_options =  [''=>'&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
					{!! Form::select('filter_rpp', $rpp_options, (isset($filters['filter_rpp']) ? $filters['filter_rpp'] : NULL), ['class' =>'form-control']) !!}
				</div>
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

		</div>
		<!-- END: FILTERS -->
	</div>


	@include('series.list', ['series' => $series])

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