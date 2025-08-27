@extends('app')

@section('title','Blog Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h1 class="display-6 text-primary">Blog . Edit . {{ $blog->name }}</h1>

	{!! Form::model($blog, ['route' => ['blogs.update', $blog->slug], 'method' => 'PATCH']) !!}

		@include('blogs.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['blogs.destroy', $blog->slug]) !!}
	<br>
	{!! link_to_route('blogs.index','Return to list') !!}
@stop

@section('scripts.footer')
<script>
    window.Dropzone["autoDiscover"] = false;

    $('input.delete').on('click', function(e){
        e.preventDefault();
        const form = $(this).parents('form');
        Swal.fire({
            title: "Are you sure?",
            text: "You will not be able to recover this blog!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            preConfirm: function() {
                return new Promise(function(resolve) {
                    setTimeout(function() {
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
                console.log('Cancelled confirm')
            }
        });
    })
</script>
@stop
