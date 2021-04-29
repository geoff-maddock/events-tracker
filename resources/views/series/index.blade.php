@extends('app')

@section('content')

<h4>Event Series</h4>

<div id="action-menu" style="margin-bottom: 5px;">
    <a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
    <a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show current series</a>
    <a href="{!! URL::route('series.cancelled') !!}" class="btn btn-info">Show cancelled series</a>
    <a href="{!! URL::route('series.export') !!}" class="btn btn-primary" target="_blank">Export</a>
</div>

<div id="filters-container" class="row">
    <div id="filters-content" class="col-lg-9">
        <a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle"
                class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>

                {!! Form::open(['route' => [$filterRoute ?? 'series.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

                <div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
                    style="@if (!$hasFilter) display: none; @endif">

            <div class="form-group col-sm-2">

                {!! Form::label('filter_name','Filter By Name') !!}

                {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
                [
                    'class' =>'form-control',
                    'name' => 'filters[name]'
                    ]) !!}
            </div>

            <div class="form-group col-sm-2">

                {!! Form::label('filter_occurrence_type','Occurrence Type') !!}
                {!! Form::select('filter_occurrence_type', $occurrenceTypeOptions, (isset($filters['occurrence_type']) ?
                $filters['occurrence_type'] : NULL), 
                [
                    'class' =>'form-control',
                    'name' => 'filters[occurrence_type]'
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_occurrence_week','Week') !!}
                {!! Form::select('filter_occurrence_week', $occurrenceWeekOptions, (isset($filters['occurrence_week']) ?
                $filters['occurrence_week'] : NULL),
                [
                    'class' => 'form-control',
                    'name' => 'filters[occurrence_week]'
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_occurrence_day','Day') !!}
                {!! Form::select('filter_occurrence_day', $occurrenceDayOptions, (isset($filters['occurrence_day']) ?
                $filters['occurrence_day'] : NULL), 
                [
                    'class' => 'form-control',
                    'name' => 'filters[occurrence_day]',
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_tag','Tag') !!}
                {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
                [
                    'data-theme'=> 'bootstrap',
                    'data-width' => '100%', 
                    'class' =>'form-control select2',
                    'data-placeholder' => 'Select a tag',
                    'name' => 'filters[tag]'
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_visibility','Visibility') !!}
                {!! Form::select('filter_visibility', $visibilityOptions, (isset($filters['visibility']) ? $filters['visibility'] : NULL),
                [
                    'data-theme'=> 'bootstrap',
                    'data-width' => '100%', 
                    'class' =>'form-control select2',
                    'data-placeholder' => 'Select a visibility',
                    'name' => 'filters[visibility]'
                ]) !!}
            </div>

            <div class="col-sm-2">
                <div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
                    'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['series.reset'], 'method' => 'GET']) !!}
                    {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
                    'primary-filter-reset']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
        <form action="{{ url()->action('SeriesController@filter') }}" method="GET" class="form-inline">
            <div class="form-group">
                <a href="{{ url()->action('SeriesController@rppReset') }}" class="btn btn-primary">
                    <span class="glyphicon glyphicon-repeat"></span>
                </a>
				{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
				{!! Form::select('sort', $sortOptions, ($sort ?? 'events.start_at'), ['class' =>'form-control
				auto-submit'])
				!!}
				{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' =>'form-control
				auto-submit']) !!}
            </div>
        </form>
    </div>

</div>

<br style="clear: left;" />

<div id="list-container" class="row">
    <div class="col-md-12 col-lg-6">
        @if (!$series->isEmpty())
        {!! $series->render() !!}
        @endif
        @include('series.list', ['series' => $series])
        @if (!$series->isEmpty())
        {!! $series->render() !!}
        @endif
    </div>
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