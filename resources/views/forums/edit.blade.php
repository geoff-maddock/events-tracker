@extends('app')

@section('title','Forum Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h4>Forum . EDIT 
	@include('forums.crumbs', ['slug' => $forum->slug ? $forum->slug : $forum->id])
</h4>

	{!! Form::model($forum, ['route' => ['forums.update', $forum->id], 'method' => 'PATCH']) !!}

		@include('forums.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['forums.destroy', $forum->id]) !!}</P>

	<P><a href="{!! URL::route('forums.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
