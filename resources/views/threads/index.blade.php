@extends('app')

@section('title','Forum')

@section('content')

<h4>Forum @include('threads.crumbs')</h4>

<div id="action-menu" style="margin-bottom: 5px;">
    <a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
    <a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paged threads</a>
    <a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>
</div>

<div id="filters-container" class="row">
    <div id="filters-content" class="col-lg-9">

        <a href="#" id="filters" class="btn btn-primary">Filters
            <span id="filters-toggle"
                class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
        {!! Form::open(['route' => ['threads.filter'], 'method' => 'GET']) !!}

        <div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif" <!-- BEGIN: FILTERS -->

            <div class="form-group col-sm-2 ">

                {!! Form::label('filter_name','Filter By Name') !!}

                {!! Form::text('filter_name', (isset($filters['filter_name']) ? $filters['filter_name'] : NULL),
                ['class' =>'form-control']) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_user_id','Filter By User') !!}
                <?php $users = ['' => '&nbsp;'] + App\Models\User::orderBy('name', 'ASC')->pluck('name', 'name')->all(); ?>
                {!! Form::select('filter_user', $users, (isset($filters['filter_user']) ? $filters['filter_user'] :
                NULL), ['data-theme' => 'bootstrap','data-width' => '100%', 'class' =>'form-control select2
                auto-submit', 'data-placeholder' => 'Select a user']) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_tag','Filter By Tag') !!}
                <?php $tags = ['' => '&nbsp;'] + App\Models\Tag::orderBy('name', 'ASC')->pluck('name', 'name')->all(); ?>
                {!! Form::select('filter_tag', $tags, (isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL),
                ['data-theme' => 'bootstrap', 'data-width' => '100%','class' =>'form-control select2 auto-submit',
                'data-placeholder' => 'Select a tag']) !!}
            </div>

            <div class="col-sm-2">
                <div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
                    'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['threads.reset'], 'method' => 'GET']) !!}
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
                <a href="{{ url()->action('ThreadsController@rppReset') }}" class="btn btn-primary">
                    <span class="glyphicon glyphicon-repeat"></span>
                </a>
                <?php $rpp_options = [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000]; ?>
                <?php $sort_by_options = ['name' => 'Name', 'created_by' => 'User', 'created_at' => 'Created At']; ?>
                <?php $sort_order_options = ['asc' => 'asc', 'desc' => 'desc']; ?>
                {!! Form::select('rpp', $rpp_options, ($rpp ?? 10), ['class' =>'form-control auto-submit']) !!}
                {!! Form::select('sortBy', $sort_by_options, ($sortBy ?? 'name'), ['class' =>'form-control
                auto-submit']) !!}
                {!! Form::select('sortOrder', $sort_order_options, ($sortOrder ?? 'asc'), ['class' =>'form-control
                auto-submit']) !!}
            </div>
        </form>
    </div>
</div>

<br style="clear: left;" />

<div id="list-container" class="row">


    <div class="col-lg-12">
        @if (isset($threads) && count($threads) > 0)
        {!! $threads->render() !!}
        @include('threads.list', ['threads' => $threads])
        {!! $threads->render() !!}
        @else
        No matching threads found.
        @endif
    </div>


</div>
@endsection

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