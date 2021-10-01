@extends('app')

@section('title','Group Edit')

@section('content')

<h1 class="display-6 text-primary">Group . Edit . {{ $group->name }}</h1>
<div id="action-menu" class="mb-2">
 	<a href="{!! route('groups.show', ['group' => $group->id]) !!}" class="btn btn-primary">Show Group</a> <a href="{!! URL::route('groups.index') !!}" class="btn btn-info">Return to list</a>
</div>

{!! Form::model($group, ['route' => ['groups.update', $group->id], 'method' => 'PATCH']) !!}

	@include('groups.form', ['action' => 'update'])

{!! Form::close() !!}

{!! delete_form(['groups.destroy', $group->id]) !!}

@stop
