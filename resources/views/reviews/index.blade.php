@extends('app')

@section('title','Reviews')

@section('content')

<h1 class="display-6 text-primary">Reviews @include('reviews.crumbs')</h1>

<div id="action-menu" class="mb-2">
</div>

<div id="filters-container" class="row my-2">
    <div id="filters-content" class="col-xl-9">
        <a href="#" id="filters" class="btn btn-primary">
        Filters
        <span id="filters-toggle" class="@if (!$hasFilter) filter-closed @else filter-open @endif">
            <i class="bi bi-chevron-down"></i>
        </span>
        </a>
        {!! Form::open(['route' => [$filterRoute ?? 'reviews.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
        
		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
            <div class="row">
                <div class="col">
                    {!! Form::label('filter_review','Filter By Review') !!}

                    {!! Form::text('filter_review', (isset($filters['review']) ? $filters['review'] : NULL),
                    ['class' =>'form-control form-background', 'name' => 'filters[review]']) !!}
                </div>

                <div class="col">
                    {!! Form::label('filter_user','Filter By User') !!}

                    {!! Form::select('filter_user', $userOptions, (isset($filters['user']) ? $filters['user'] :
                    NULL), 
                    [
                        'data-theme' => 'bootstrap-5',
                        'data-width' => '100%', 
                        'class' => 'form-control select2', 
                        'data-placeholder' => 'Select a user',
                        'name' => 'filters[user]'
                        ]) !!}
                </div>

                <div class="col">
                    {!! Form::label('filter_review_type', 'Type') !!}
                    {!! Form::select('filter_review_type', $reviewTypeOptions, (isset($filters['review_type']) ? $filters['review_type'] : NULL),
                    [
                        'data-theme' => 'bootstrap-5',
                        'data-width' => '100%',
                        'class' => 'form-control select2',
                        'data-placeholder' => 'Select a type',
                        'name' => 'filters[review_type]'
                    ]) !!}
                </div>
            </div>

            <div class="row my-2">
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
</div>
<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right">
    <form action="{{ url()->current() }}" method="GET" class="form-inline">
        <div class="form-group row gx-1 justify-content-end">
            <div class="col-auto">
                <a href="{{ url()->action('PermissionsController@rppReset') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
            <div class="col-auto">
                {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-select form-background auto-submit']) !!}
            </div>
            <div class="col-auto">
                {!! Form::select('sort', $sortOptions, ($sort ?? 'name'), ['class' => 'form-select form-background auto-submit']) !!}
            </div>
            <div class="col-auto">               
                {!! Form::select('direction', $directionOptions, ($direction ?? 'asc'), ['class' =>'form-select form-background auto-submit']) !!}
            </div>
        </form>
    </div>
</div>


<div class="row my-2">

    @if (isset($reviews) && count($reviews) > 0)
    <div class="col-lg-6">
                    {!! $reviews->render() !!}
                    {!! $reviews->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->render() !!}
                    @include('reviews.list', ['reviews' => $reviews])
    </div>
    @endif

</div>

@stop

@section('footer')
@include('partials.filter-js')
@endsection