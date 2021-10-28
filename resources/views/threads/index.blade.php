@extends('app')

@section('title','Forum')

@section('content')

<h1 class="display-6 text-primary">Forum @include('threads.crumbs')</h1>

<div id="action-menu" class="mb-2">
    <a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
    <a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paged threads</a>
    <a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>
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
        
        {!! Form::open(['route' => [$filterRoute ?? 'threads.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
            <div class="row">
                <div class="col-sm">
                    {!! Form::label('filter_name','Filter By Name') !!}

                    {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
                    [
                        'class' =>'form-control form-background',
                        'name' => 'filters[name]'
                    ]
                    ) !!}
                </div>

                <div class="col-sm">
                    {!! Form::label('filter_user','Filter By User') !!}
                    {!! Form::select('filter_user', $userOptions, (isset($filters['user']) ? $filters['user'] :
                    NULL), 
                    [
                        'data-theme' => 'bootstrap-5',
                        'data-width' => '100%', 
                        'class' => 'form-select select2 form-background', 
                        'data-placeholder' => 'Select a user',
                        'name' => 'filters[user]'
                        ]) !!}
                </div>

                <div class="col-sm">
                    {!! Form::label('filter_tag','Filter By Tag') !!}
                    {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
                    [
                        'data-theme' => 'bootstrap-5',
                        'data-width' => '100%',
                        'class' => 'form-select select2 form-background',
                        'data-placeholder' => 'Select a tag',
                        'name' => 'filters[tag]'
                    ]
                    ) !!}
                </div>
            </div>
                <div class="row my-2">
                    <div class="col-sm-2">
                        <div class="btn-group col-sm-1">
                        <label></label>
                        {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
                        'primary-filter-submit']) !!}
                        {!! Form::close() !!}
                        {!! Form::open(['route' => ['threads.reset'], 'method' => 'GET']) !!}
                        {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>  'primary-filter-reset']) !!}
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
	<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right my-2">
		<form action="{{ url()->current() }}" method="GET" class="form-inline">
			<div class="form-group row gx-1 justify-content-end">
				<div class="col-auto">
					<a href="{{ url()->action('ThreadsController@rppReset') }}" class="btn btn-primary">
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
					{!! Form::select('sort', $sortOptions, ($sort ?? 'threads.created_at'), ['class' => 'form-background form-select auto-submit']) !!}
				</div>
				<div class="col-auto">
					{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' => 'form-background form-select auto-submit']) !!}
				</div>
			</div>
		</form>
	</div>
</div>

<br style="clear: left;" />

<div id="list-container" class="row">

    <div class="col-lg-12">
        @if (isset($threads) && count($threads) > 0)
        {!! $threads->onEachSide(2)->links() !!}
        @include('threads.list', ['threads' => $threads])
        {!! $threads->onEachSide(2)->links() !!}
        @else
        No matching threads found.
        @endif
    </div>

</div>
@endsection

@section('footer')
@include('partials.filter-js')
@endsection