@extends('app')

@section('title','Entity Type Edit')

@section('content')

    <h4>Entity Type. Edit
		@include('entityTypes.crumbs', ['slug' => $entityType->slug ?: $entityType->id])
	</h4>
    <a href="{!! route('entity-types.show', ['entity_type' => $entityType->id]) !!}" class="btn btn-primary">Show entity type</a>
    <a href="{!! URL::route('entity-types.index') !!}" class="btn btn-info">Return to list</a>

	{!! Form::model($entityType, ['route' => ['entity-types.update', $entityType->id], 'method' => 'PATCH']) !!}

		@include('entityTypes.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['entity-types.destroy', $entityType->id]) !!}

	{!! link_to_route('entity-types.index','Return to list') !!}
@stop
