@extends('app')

@section('title','Entities')

@section('content')

	<h4>Entities
		@include('entities.crumbs')
	</h4>

	<P>
		<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
		<a href="{!! URL::route('entities.role', ['role' => 'artist']) !!}" class="btn btn-info">Show artists</a>
		<a href="{!! URL::route('entities.role', ['role' => 'dj']) !!}" class="btn btn-info">Show DJs</a>
		<a href="{!! URL::route('entities.role', ['role' => 'producer']) !!}" class="btn btn-info">Show producers</a>
		<a href="{!! URL::route('entities.role', ['role' => 'promoter']) !!}" class="btn btn-info">Show promoters</a>
		<a href="{!! URL::route('entities.role', ['role' => 'venue']) !!}" class="btn btn-info">Show venues</a>
	</P>

	<!-- NAV / FILTER -->
	<div class="row" class="tab-content filters-content">
		<div class="col-sm-12">
			{!! Form::open(['route' => ['entities.filter'], 'method' => 'GET']) !!}

			<!-- BEGIN: FILTERS -->
			@if ($hasFilter)

			<div class="form-group col-sm-4">

			{!! Form::label('filter_name','Filter By Name') !!}

			{!! Form::text('filter_name', (isset($name) ? $name : NULL), ['class' =>'form-control']) !!}
			</div>

			<div class="form-group col-sm-2">

			{!! Form::label('filter_role','Filter By Role') !!}
			<?php $roles = [''=>'&nbsp;'] + App\Role::orderBy('name', 'ASC')->lists('name', 'name')->all();?>
			{!! Form::select('filter_role', $roles, (isset($role) ? $role : NULL), ['class' =>'form-control']) !!}
			</div>

			<div class="form-group col-sm-2">
			{!! Form::label('filter_tag','Filter By Tag') !!}
			<?php $tags =  [''=>'&nbsp;'] + App\Tag::orderBy('name','ASC')->lists('name', 'name')->all();?>
			{!! Form::select('filter_tag', $tags, (isset($tag) ? $tag : NULL), ['class' =>'form-control']) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_rpp','RPP') !!}
				<?php $rpp_options =  [''=>'&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
				{!! Form::select('filter_rpp', $rpp_options, (isset($rpp) ? $rpp : NULL), ['class' =>'form-control']) !!}
			</div>
			@endif

			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
				{!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}

				{!! Form::close() !!}

				{!! Form::open(['route' => ['entities.reset'], 'method' => 'GET']) !!}

					{!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}

				{!! Form::close() !!}
				</div>
			</div>

		</div>
			<!-- END: FILTERS -->
	</div>

	<br style="clear: left;"/>

    <div class="row">
        <div class='col-md-12 col-lg-6'>
            {!! $entities->appends(['sort_by' => $sortBy,
                                    'rpp' => $rpp,
                                    'filter_role' => isset($filter_role) ? $filter_role : NULL,
                                    'filter_tag' => isset($filter_tag) ? $filter_tag : NULL,
                                    'filter_name' => isset($filter_name) ? $filter_name : NULL,
            ])->render() !!}
            @include('entities.list', ['entities' => $entities])
        </div>
    </div>
@stop
