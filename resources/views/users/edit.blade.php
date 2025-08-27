@extends('app')

@section('title', 'User Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

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
                preConfirm: function () {
                    return new Promise(function (resolve) {
                        setTimeout(function () {
                            resolve()
                        }, 2000)
                    })
                }
            }).then(result => {
                if (result.value) {
                    // handle Confirm button click
                    // result.value will contain `true` or the input value
                    form.submit();
                } else {
                    // handle dismissals
                    // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                    console.log('cancelled confirm')
                }
            });
    })
</script>
@stop
