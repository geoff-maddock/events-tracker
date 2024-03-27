@extends('app')

@section('title','Group Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

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
