@extends('app')

@section('title','Thread Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h1 class="display-6 text-primary">Thread . Edit
	@include('threads.crumbs', ['slug' => $thread->slug ? $thread->slug : $thread->id])
</h1>

	{!! Form::model($thread, ['route' => ['threads.update', $thread->id], 'method' => 'PATCH']) !!}

		@include('threads.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['threads.destroy', $thread->id]) !!}</P>

	<P><a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
