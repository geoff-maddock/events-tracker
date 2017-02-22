@extends('app')

@section('title','Entities')

@section('content')

		<h1>Entities 
				@include('entities.crumbs')
		</h1>

		<P><a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a></P>

		<!-- NAV / FILTER -->
		<div class="row">
				{!! Form::open(['route' => ['entities.filter'], 'method' => 'GET']) !!}

				<div class="form-group col-md-2">

				{!! Form::label('filter_name','Filter By Name') !!}

				{!! Form::text('filter_name', (isset($name) ? $name : NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-md-2">

				{!! Form::label('filter_role','Filter By Role') !!}
				<?php $roles = [''=>'&nbsp;'] + App\Role::orderBy('name', 'ASC')->lists('name', 'name')->all();;?>
				{!! Form::select('filter_role', $roles, (isset($role) ? $role : NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-md-2">
				{!! Form::label('filter_tag','Filter By Tag') !!}
				<?php $tags =  [''=>'&nbsp;'] + App\Tag::orderBy('name','ASC')->lists('name', 'name')->all();;?>
				{!! Form::select('filter_tag', $tags, (isset($tag) ? $tag : NULL), ['class' =>'form-control']) !!}
				</div>
				<div class="btn-group">
				{!! Form::submit('Filter',  ['class' =>'btn btn-primary']) !!}
				</div>
				{!! Form::close() !!}

				{!! Form::open(['route' => ['entities.index'], 'method' => 'GET']) !!}
				<div class="btn-group">
				{!! Form::submit('Reset',  ['class' =>'btn btn-primary']) !!}
				</div>
				{!! Form::close() !!}

		</div>

		<div class='col-md-6'>
		@include('entities.list', ['entities' => $entities])
		</div>

@stop
