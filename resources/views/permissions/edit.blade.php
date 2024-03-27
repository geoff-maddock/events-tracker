@extends('app')

@section('title','Permission Edit')


@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')


<h1 class="display-6 text-primary">Permission . Edit . {{ $permission->name }}</h1>

	{!! Form::model($permission, ['route' => ['permissions.update', $permission->id], 'method' => 'PATCH']) !!}

		@include('permissions.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['permissions.destroy', $permission->id]) !!}

	{!! link_to_route('permissions.index','Return to list') !!}
@stop
