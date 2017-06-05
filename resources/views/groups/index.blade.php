@extends('app')

@section('title','Groups')

@section('content')

		<h4>Groups 
				@include('groups.crumbs')
		</h4>

		<P><a href="{!! URL::route('groups.create') !!}" class="btn btn-primary">Add a group</a></P>

		<!-- NAV / FILTER -->
		<div class="row nav">

		</div>

		<div class='col-md-6'>
		@include('groups.list', ['groups' => $groups])
		{!! $groups->render() !!}
		</div>

@stop
