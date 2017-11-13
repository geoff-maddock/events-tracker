@extends('app')

@section('title','Forum')

@section('content')

	<h1>Forum
		@include('threads.crumbs')
	</h1>

	<p>
	<a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
	<a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paginated threads</a>
	<a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>

	<!-- NAV / FILTER -->
	<div class="row" class="tab-content filters-content">
		<div class="col-sm-12">
			{!! Form::open(['route' => ['threads.filter'], 'method' => 'GET']) !!}

			<!-- BEGIN: FILTERS -->

			<div class="form-group col-sm-2 ">

			{!! Form::label('filter_name','Filter By Name') !!}

			{!! Form::text('filter_name', (isset($filters['filter_name']) ? $filters['filter_name'] : NULL), ['class' =>'form-control']) !!}
			</div>

			<div class="form-group col-sm-2">
			{!! Form::label('filter_user_id','Filter By User') !!}
			<?php $users = [''=>'&nbsp;'] + App\User::orderBy('name', 'ASC')->pluck('name', 'name')->all();?>
			{!! Form::select('filter_user', $users, (isset($filters['filter_user']) ? $filters['filter_user'] : NULL), ['class' =>'form-control select2  auto-submit', 'data-placeholder' => 'Select a user']) !!}
			</div>

			<div class="form-group col-sm-2">
			{!! Form::label('filter_tag','Filter By Tag') !!}
			<?php $tags =  [''=>'&nbsp;'] + App\Tag::orderBy('name','ASC')->pluck('name', 'name')->all();?>
			{!! Form::select('filter_tag', $tags, (isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL), ['class' =>'form-control select2  auto-submit', 'data-placeholder' => 'Select a tag']) !!}
			</div>

			<div class="form-group col-sm-1">
				{!! Form::label('filter_rpp','RPP') !!}
				<?php $rpp_options =  [''=>'&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
				{!! Form::select('filter_rpp', $rpp_options, (isset($filters['filter_rpp']) ? $filters['filter_rpp'] : NULL), ['class' =>'form-control auto-submit']) !!}
			</div>


			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
				{!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}

				{!! Form::close() !!}

				{!! Form::open(['route' => ['threads.reset'], 'method' => 'GET']) !!}

					{!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}

				{!! Form::close() !!}
				</div>
			</div>

		</div>
			<!-- END: FILTERS -->
	</div>

	<div class="row">

	@if (isset($threads) && count($threads) > 0)
	<div class="col-lg-12">
					{!! $threads->appends(['sort_by' => $sortBy,
										'rpp' => $rpp,
										'filter_user' => isset($filter_user) ? $filter_user : NULL,
										'filter_tag' => isset($filter_tag) ? $filter_tag : NULL,
										'filter_name' => isset($filter_name) ? $filter_name : NULL,
					])->render() !!}
		@include('threads.list', ['threads' => $threads])
	</div>
	@endif

@stop
 