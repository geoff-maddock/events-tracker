@extends('app')

@section('content')

<h1 class="display-6 text-primary">Add a New Group</h1>

{!! Form::open(['route' => 'groups.store']) !!}

	@include('groups.form')

{!! Form::close() !!}

{!! link_to_route('groups.index', 'Return to list') !!}
@stop
