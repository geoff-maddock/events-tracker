@extends('app')

@section('title','Reviews')

@section('content')

<h4>Reviews @include('reviews.crumbs')</h4>

<div id="action-menu" style="margin-bottom: 5px;">
</div>

<div id="filters-container" class="row">
    <div id="filters-content" class="col-lg-9">
        <a href="#" id="filters" class="btn btn-primary">
            Filters
            <span id="filters-toggle"
                class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
        {!! Form::open(['route' => [$filterRoute ?? 'reviews.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
        
        <div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
        style="@if (!$hasFilter) display: none; @endif">

            <div class="form-group col-sm-2">
                {!! Form::label('filter_review','Filter By Review') !!}

                {!! Form::text('filter_review', (isset($filters['review']) ? $filters['review'] : NULL),
                ['class' =>'form-control', 'name' => 'filters[review]']) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_review_type','Type') !!}
                {!! Form::select('filter_review_type', $reviewTypeOptions, (isset($filters['review_type'])
                ? $filters['review_type'] : NULL),
                [
                'data-theme' => 'bootstrap',
                'data-width' => '100%',
                'class' => 'form-control select2',
                'data-placeholder' => 'Select a type',
                'name' => 'filters[review_type]'
                ]) !!}
            </div>

            <div class="col-sm-2">
                <div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Filter', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
                    'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['reviews.reset'], 'method' => 'GET']) !!}
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
                {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
                {!! Form::select('sort', $sortOptions, ($sort ?? 'name'), ['class' =>'form-control
                auto-submit']) !!}
                {!! Form::select('direction', $directionOptions, ($direction ?? 'asc'), ['class' =>'form-control
                auto-submit']) !!}
            </div>
        </form>
    </div>
</div>


<br style="clear: left;" />

<div class="row">

    @if (isset($reviews) && count($reviews) > 0)
    <div class="col-lg-6">
        <div class="bs-component">
            <div class="panel panel-info">

                <div class="panel-heading">
                    <h3 class="panel-title">Events</h3>
                </div>

                <div class="panel-body">
                    {!! $reviews->render() !!}
                    {!! $reviews->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->render() !!}
                    @include('reviews.list', ['reviews' => $reviews])
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