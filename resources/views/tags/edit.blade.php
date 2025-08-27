@extends('app')

@section('title', 'Keyword Tag Edit')

@section('content')

<h1 class="display-crumbs text-primary">Keyword Tag . Edit . {{ $tag->name }}</h1>
<div id="action-menu" class="mb-2">
	<a href="{!! route('tags.show', ['tag' => $tag->slug]) !!}" class="btn btn-primary">Show Tag</a>
	<a href="{!! URL::route('tags.index') !!}" class="btn btn-info">Return to list</a>
</div>


	{!! Form::model($tag, ['route' => ['tags.update', $tag->slug], 'method' => 'PATCH']) !!}

		@include('tags.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['tags.destroy', $tag->id]) !!}

@stop
@section('scripts.footer')
<script>
    $('input.delete').on('click', function(e){
        e.preventDefault();
        const form = $(this).parents('form');
        Swal.fire({
            title: "Are you sure?",
            text: "You will not be able to recover this tag!",
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
