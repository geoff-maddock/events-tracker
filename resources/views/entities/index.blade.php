@extends('app')

@section('title','Entities')

@section('content')

<h4>Entities
    @include('entities.crumbs')
</h4>

<div id="action-menu" style="margin-bottom: 5px;">
    <a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
    <a href="{!! URL::route('entities.role', ['role' => 'artist']) !!}" class="btn btn-info">Show artists</a>
    <a href="{!! URL::route('entities.role', ['role' => 'band']) !!}" class="btn btn-info">Show bands</a>
    <a href="{!! URL::route('entities.role', ['role' => 'dj']) !!}" class="btn btn-info">Show DJs</a>
    <a href="{!! URL::route('entities.role', ['role' => 'producer']) !!}" class="btn btn-info">Show producers</a>
    <a href="{!! URL::route('entities.role', ['role' => 'promoter']) !!}" class="btn btn-info">Show promoters</a>
    <a href="{!! URL::route('entities.role', ['role' => 'shop']) !!}" class="btn btn-info">Show shops</a>
    <a href="{!! URL::route('entities.role', ['role' => 'venue']) !!}" class="btn btn-info">Show venues</a>
</div>

<div id="filters-container" class="row">
    <div id="filters-content" class="col-lg-9">
        <a href="#" id="filters" class="btn btn-primary">
            Filters
            <span id="filters-toggle"
                class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
        {!! Form::open(['route' => ['entities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

        <div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
            style="@if (!$hasFilter) display: none; @endif">

            <!-- BEGIN: FILTERS -->
            <div class="form-group col-sm-2">
                {!! Form::label('filter_name','Filter By Name') !!}

                {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
                ['class' =>'form-control', 'name' => 'filters[name]']) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_role','Filter By Role', array('width' => '100%')) !!}<br>
                {!! Form::select('filter_role', $roleOptions, (isset($filters['role']) ? $filters['role']
                : NULL),
                [
                'data-theme' => 'bootstrap',
                'data-style' => '100%',
                'data-width' => '100%',
                'class' => 'form-control select2',
                'data-placeholder' => 'Select a role',
                'name' => 'filters[role]'
                ]) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_tag','Filter By Tag', array('width' => '100%')) !!}
                {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] :
                NULL),
                [
                'data-theme' => 'bootstrap',
                'class' =>'form-control select2',
                'data-width' => '100%',
                'data-placeholder' => 'Select a tag',
                'name' => 'filters[tag]'
                ])
                !!}
            </div>

            <div class="form-group col-sm-2">
				{!! Form::label('filter_entity_type','Type') !!}
				{!! Form::select('filter_entity_type', $entityTypeOptions, (isset($filters['entity_type'])
				? $filters['entity_type'] : NULL),
				[
				'data-theme' => 'bootstrap',
				'data-width' => '100%',
				'class' => 'form-control select2',
				'data-placeholder' => 'Select a type',
				'name' => 'filters[entity_type]'
				]) !!}
			</div>

            <div class="col-sm-2">
                <div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
                    'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['entities.reset'], 'method' => 'GET']) !!}
                    {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
                    'primary-filter-reset']) !!}
                    {!! Form::close() !!}
                </div>
            </div>

        </div>
    </div>

    <div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
        <form action="{{ url()->action('EntitiesController@filter') }}" method="GET" class="form-inline">
            <div class="form-group">
                <a href="{{ url()->action('EntitiesController@rppReset') }}" class="btn btn-primary"><span
                        class="glyphicon glyphicon-repeat"></span></a>
                {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
                {!! Form::select('sort', $sortOptions, ($sort ?? 'events.name'), ['class' =>'form-control auto-submit'])
                !!}
                {!! Form::select('direction', $directionOptions, ($direction ?? 'asc'), ['class' =>'form-control
                auto-submit']) !!}
            </div>
        </form>
    </div>

</div>

<br style="clear: left;" />

<div id="list-container" class="row">
    <div class="col-md-12 col-lg-6">

        @if (!$entities->isEmpty())
        {!! $entities->render() !!}
        @endif

        @include('entities.list', ['entities' => $entities])

        @if (!$entities->isEmpty())
        {!! $entities->render() !!}
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