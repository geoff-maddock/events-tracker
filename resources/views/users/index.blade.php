@extends('app')

@section('title','Users')

@section('content')

    <h4>Users</h4>
    <i>public user directory</i><br>

    <div id="filters-container" class="row">
        <div id="filters-content" class="col-lg-9">

        <a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle" class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
        {!! Form::open(['route' => ['users.filter'], 'method' => 'GET']) !!}

        <div id="filter-list" @if (!$hasFilter)style="display: none"@endif >
            <!-- BEGIN: FILTERS -->
            <div class="form-group col-sm-3">
                {!! Form::label('filter_email','Email') !!}
                {!! Form::text('filter_email', ($filters['filter_email'] ?? NULL), ['class' =>'form-control']) !!}
            </div>

            <div class="form-group col-sm-3">
                {!! Form::label('filter_name','Name') !!}
                {!! Form::text('filter_name', ($filters['filter_name'] ?? NULL), ['class' =>'form-control']) !!}
            </div>

            <div class="form-group col-sm-2">
                {!! Form::label('filter_status','Status') !!}
                <?php $venues = [''=>''] + App\UserStatus::orderBy('name','ASC')->pluck('name','name')->all();?>
                {!! Form::select('filter_status', $venues, $filters['filter_status'] ?? NULL, ['data-theme' => 'bootstrap', 'data-width' => '100%','class' =>'form-control select2', 'data-placeholder' => 'Select a status']) !!}
            </div>

            <div class="col-sm-2">
                <div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['users.reset'], 'method' => 'GET']) !!}
                    {!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
        <form action="{{ url()->action('UsersController@filter') }}" method="GET" class="form-inline">
            <div class="form-group">
                <?php $rpp_options =  [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
                <?php $sort_by_options = ['name' => 'Username',  'created_at' => 'Created']; ?>
                <?php $sort_order_options = ['asc' => 'asc', 'desc' => 'desc']; ?>
                {!! Form::select('rpp', $rpp_options, ($rpp ?? 10), ['class' =>'form-control auto-submit']) !!}
                {!! Form::select('sortBy', $sort_by_options, ($sortBy ?? 'name'), ['class' =>'form-control auto-submit']) !!}
                {!! Form::select('sortOrder', $sort_order_options, ($sortOrder ?? 'asc'), ['class' =>'form-control auto-submit']) !!}
            </div>
        </form>
    </div>
    </div>

    <br style="clear: left;"/>

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
