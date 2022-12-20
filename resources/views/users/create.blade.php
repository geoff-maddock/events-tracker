@extends('app')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h1 class="display-6 text-primary">Add a New User</h1>

{!! Form::open(['route' => 'users.store']) !!}

	@include('users.form')

{!! Form::close() !!}

{!! link_to_route('users.index', 'Return to list') !!}
@stop
