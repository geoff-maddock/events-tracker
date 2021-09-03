@extends('app')

@section('content')

<h1 class="display-6 text-primary">Event Series @include('series.crumbs')</h1>

<div id="action-menu" class="mb-2">
    <a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
    <a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show current series</a>
    <a href="{!! URL::route('series.cancelled') !!}" class="btn btn-info">Show cancelled series</a>
    <a href="{!! URL::route('series.export') !!}" class="btn btn-primary" target="_blank">Export</a>
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
                <div class="col">

                {!! Form::label('filter_name','Filter By Name') !!}

                {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
                [
                    'class' => 'form-control form-background',
                    'name' => 'filters[name]'
                    ]) !!}
            </div>

            <div class="form-group col-sm-2">

                {!! Form::label('filter_occurrence_type','Occurrence Type') !!}
                {!! Form::select('filter_occurrence_type', $occurrenceTypeOptions, (isset($filters['occurrence_type']) ?
                $filters['occurrence_type'] : NULL), 
                [
                    'class' => 'form-control form-background',
                    'name' => 'filters[occurrence_type]'
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_occurrence_week','Week') !!}
                {!! Form::select('filter_occurrence_week', $occurrenceWeekOptions, (isset($filters['occurrence_week']) ?
                $filters['occurrence_week'] : NULL),
                [
                    'class' => 'form-control form-background',
                    'name' => 'filters[occurrence_week]'
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_occurrence_day','Day') !!}
                {!! Form::select('filter_occurrence_day', $occurrenceDayOptions, (isset($filters['occurrence_day']) ?
                $filters['occurrence_day'] : NULL), 
                [
                    'class' => 'form-control form-background',
                    'name' => 'filters[occurrence_day]',
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_tag','Tag') !!}
                {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
                [
                    'data-theme'=> 'bootstrap-5',
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
                    'data-theme'=> 'bootstrap-5',
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
</div>
<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right">
        <form action="{{ url()->action('SeriesController@filter') }}" method="GET" class="form-inline">
			<div class="form-group row gx-1 justify-content-end">
                <div class="col-auto">
                    <a href="{{ url()->action('SeriesController@rppReset') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                        </svg>
                    </a>
                </div>
                <div class="col-auto">
				    {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-select form-background auto-submit']) !!}
                </div>
                <div class="col-auto">
                    {!! Form::select('sort', $sortOptions, ($sort ?? 'events.start_at'), ['class' =>'form-select form-background auto-submit']) !!}
                </div>
                <div class="col-auto">
    				{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' =>'form-select form-background auto-submit']) !!}
                </div>
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
@include('partials.filter-js')
@endsection