@extends('app')

@section('title','User Edit')

@section('content')

	<h2>{{ $user->name }}</h2>
	<p>

		<a href="{!! route('users.show', ['user' => $user->id]) !!}" class="btn btn-primary">Show Profile</a>
		<a href="{!! URL::route('users.index') !!}" class="btn btn-info">Return to list</a>

	</p>

	{!! Form::model($user->profile, ['route' => ['users.update', $user->id], 'method' => 'PATCH']) !!}

		@include('users.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['users.destroy', $user->id]) !!}</P>

@stop

@section('scripts.footer')
<script type="text/javascript">
    $('input.delete').on('click', function(e){
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this user!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: true
            },
            function(isConfirm){
                if (isConfirm)
                {
                    form.submit();
                };
            });
    })
</script>
@stop
