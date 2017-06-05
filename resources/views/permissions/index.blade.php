@extends('app')

@section('title','Permissions')

@section('content')

		<h4>Permissions
				@include('permissions.crumbs')
		</h4>

		<P><a href="{!! URL::route('permissions.create') !!}" class="btn btn-primary">Add a permission</a></P>

		<!-- NAV / FILTER -->
		<div class="row nav">

		</div>

		<div class='col-md-6'>
		@include('permissions.list', ['permissions' => $permissions])
		{!! $permissions->render() !!}
		</div>

@stop
