@extends('app')

@section('title', 'Entity Edit')

@section('content')

<h1 class="display-6 text-primary">Entity . EDIT
	@include('events.crumbs', ['slug' => $entity->name ?: $entity->id])
</h1>
      <a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Show Entity</a> <a href="{!! URL::route('entities.index') !!}" class="btn btn-info">Return to list</a>

<div class="row">
	<div class="col-md-6">

	{!! Form::model($entity, ['route' => ['entities.update', $entity->slug], 'method' => 'PATCH']) !!}

		@include('entities.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['entities.destroy', $entity->slug, 'my-2']) !!}
	</div>

	<div class="col-md-6">
		@if ($user && (Auth::user()->id === $entity->user ? $entity->user->id : null || $user->id === Config::get('app.superuser')))
		<form action="/entities/{{ $entity->id }}/photos" class="dropzone" id="myDropzone" method="POST">
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
		</form>
		@endif

		@foreach ($entity->photos->chunk(4) as $set)
		<div class="row">
		@foreach ($set as $photo)
			<div class="col-md-2">

			<a href="{{ $photo->getStoragePath() }}" data-lightbox="{{ $photo->getStoragePath() }}"><img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $entity->name}}"  class="mw-100"></a>
			@if ($user && (Auth::user()->id === $entity->user ? $entity->user->id : null || $user->id === Config::get('app.superuser')))
				{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $photo, 'DELETE', 'Delete the photo') !!}
				@if ($photo->is_primary)
				{!! link_form_bootstrap_icon('bi bi-star-fill text-primary icon', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
				@else
				{!! link_form_bootstrap_icon('bi bi-star text-info icon', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
				@endif
			@endif
			</div>
		@endforeach
		</div>
		@endforeach
	</div>
</div>
@stop

@section('scripts.footer')
<script type="text/javascript">
    window.Dropzone.autoDiscover = false;

    $(document).ready(function(){

        var myDropzone = new window.Dropzone('#myDropzone', {
            dictDefaultMessage: "Drop a file here to add an entity profile picture. (Max size 5MB)"
        });

        $('div.dz-default.dz-message > span').show(); // Show message span
        $('div.dz-default.dz-message').css({'color': '#000000', 'opacity':1, 'background-image': 'none'});

        myDropzone.options.addPhotosForm = {
		maxFilesize: 5,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
				myDropzone.on("success", function (file) {
	                location.href = 'entities/{{ $entity->slug }}';
	                location.reload();
	            });
	            myDropzone.on("successmultiple", function (file) {
	                location.href = 'entities/{{ $entity->slug }}';
	                location.reload();
	            });
				myDropzone.on("error", function (file, message) {
					Swal.fire({
						title: "Are you sure?",
						text: "You cannot upload a file that large.",
						type: "warning",
						showCancelButton: true,
						confirmButtonColor: "#DD6B55",
						confirmButtonText: "Ok",
				}).then(result => {
	                location.href = 'entities/{{ $entity->slug }}';
	                location.reload();
					});
				});
				console.log('dropzone init called');
	        },
		success: console.log('Upload successful')
	};

        myDropzone.options.addPhotosForm.init();

    });

    $('input.delete').on('click', function(e){
        e.preventDefault();
        const form = $(this).parents('form');
        Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this entity!",
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
                console.log('cancelled confirm')
            }
        });
    })
</script>
@stop
