@extends('app')

@section('title','Users')

@section('content')

<h4>Users</h4>
<i>public user directory</i><br>

<div id="filters-container" class="row">
    <div id="filters-content" class="col-lg-9">

        <a href="#" id="filters" class="btn btn-primary">
            Filters
            <span id="filters-toggle"
                class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
        {!! Form::open(['route' => ['users.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

        <div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
            style="@if (!$hasFilter) display: none; @endif">
            <!-- BEGIN: FILTERS -->
            <div class="form-group col-sm-3">
                {!! Form::label('filter_email','Email') !!}

                {!! Form::text('filter_email', (isset($filters['email']) ? $filters['email'] : NULL),
                ['class' =>'form-control', 'name' => 'filters[email]']) !!}
            </div>

            <div class="form-group col-sm-3">
                {!! Form::label('filter_name','Name') !!}

                {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
                ['class' =>'form-control', 'name' => 'filters[name]']) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_status','Status') !!}
                {!! Form::select('filter_status', $userStatusOptions, (isset($filters['status']) ? $filters['status'] :NULL), 
                [
                    'data-theme' => 'bootstrap',
                    'data-width' => '100%',
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Select a status',
                    'name' => 'filters[status]'
                ])
                !!}
            </div>

            <div class="col-sm-2">
                <div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
                    'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['users.reset'], 'method' => 'GET']) !!}
                    {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
                    'primary-filter-reset']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
        <form action="{{ url()->action('UsersController@filter') }}" method="GET" class="form-inline">
            <div class="form-group">
                <a href="{{ url()->action('UsersController@rppReset') }}" class="btn btn-primary"><span
                    class="glyphicon glyphicon-repeat"></span></a>
            {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
            {!! Form::select('sort', $sortOptions, ($sort ?? 'users.name'), ['class' =>'form-control auto-submit'])
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

        @if (!$users->isEmpty())
        {!! $users->render() !!}
        @endif

        @include('users.list', ['users' => $users])

        @if (!$users->isEmpty())
        {!! $users->render() !!}
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