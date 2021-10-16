@extends('app')

@section('title','Users')

@section('content')

<h1 class="display-6 text-primary">Users</h1>
<small class="text-muted">public user directory</small>

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
        {!! Form::open(['route' => ['users.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
            <div class="row">
                <div class="col-sm">
                    {!! Form::label('filter_email','Email') !!}

                    {!! Form::text('filter_email', (isset($filters['email']) ? $filters['email'] : NULL),
                    ['class' =>'form-control form-background', 'name' => 'filters[email]']) !!}
                </div>

                <div class="col-sm">
                    {!! Form::label('filter_name','Name') !!}

                    {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
                    ['class' =>'form-control form-background', 'name' => 'filters[name]']) !!}
                </div>

                <div class="col-sm">
                {!! Form::label('filter_status','Status') !!}
                {!! Form::select('filter_status', $userStatusOptions, (isset($filters['status']) ? $filters['status'] :NULL), 
                    [
                        'data-theme' => 'bootstrap-5',
                        'data-width' => '100%',
                        'class' => 'form-control select2',
                        'data-placeholder' => 'Select a status',
                        'name' => 'filters[status]'
                    ])
                !!}
                </div>
            </div>
            <div class="row">
            <div class="col-sm-2">
                <div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb me-2 my-2', 'id' =>
                    'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['users.reset'], 'method' => 'GET']) !!}
                    {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb me-2 my-2', 'id' =>
                    'primary-filter-reset']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</div>
<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right my-2">
        <form action="{{ url()->action('UsersController@filter') }}" method="GET" class="form-inline">
			<div class="form-group row gx-1 justify-content-end">
				<div class="col-auto">
                <a href="{{ url()->action('UsersController@rppReset') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                    </svg>
                </a>
                </div>
                <div class="col-auto">
                    {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-background form-select auto-submit']) !!}
                </div>
                <div class="col-auto">
                    {!! Form::select('sort', $sortOptions, ($sort ?? 'users.name'), ['class' => 'form-background form-select auto-submit'])   !!}
                </div>
                <div class="col-auto">
                    {!! Form::select('direction', $directionOptions, ($direction ?? 'asc'), ['class' => 'form-background form-select auto-submit']) !!}
                </div>
            </div>
        </form>
    </div>
</div>

<br style="clear: left;" />

<div id="list-container" class="row">
    <div class="col-md-12 col-lg-6">

        @if (!$users->isEmpty())
            {!! $users->onEachSide(2)->links() !!}
        @endif

        @include('users.list', ['users' => $users])

        @if (!$users->isEmpty())
            {!! $users->onEachSide(2)->links() !!}
        @endif
    </div>
</div>
@stop

@section('footer')
@include('partials.filter-js')
@endsection