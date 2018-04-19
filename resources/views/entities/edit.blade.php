@extends('app')

@section('title','Entity Edit')

@section('content')

<h2>Entity . EDIT 
	@include('events.crumbs', ['slug' => $entity->slug ?: $entity->id])
	  <br> 	<a href="{!! route('entities.show', ['id' => $entity->slug]) !!}" class="btn btn-primary">Show Entity</a> <a href="{!! URL::route('entities.index') !!}" class="btn btn-info">Return to list</a>
</h2>

<div class="row">
	<div class="col-md-6">

	{!! Form::model($entity, ['route' => ['entities.update', $entity->slug], 'method' => 'PATCH']) !!}

		@include('entities.form', ['action' => 'update', 'entityTypes' => $entityTypes])

	{!! Form::close() !!}

	{!! delete_form(['entities.destroy', $entity->slug]) !!}
	</div>

	<div class="col-md-6">
		@if ($user && (Auth::user()->id === $entity->user->id || $user->id === Config::get('app.superuser')))
		<form action="/entities/{{ $entity->id }}/photos" class="dropzone" id="myDropzone" method="POST">
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
		</form>
		@endif

		<br style="clear: left;"/>

		@foreach ($entity->photos->chunk(4) as $set)
		<div class="row">
		@foreach ($set as $photo)
			<div class="col-md-2">
			
			<a href="/{{ $photo->path }}" data-lightbox="{{ $photo->path }}"><img src="/{{ $photo->thumbnail }}" alt="{{ $entity->name}}"  style="max-width: 100%;"></a>
			@if ($user && (Auth::user()->id === $entity->user->id || $user->id === Config::get('app.superuser')))
				{!! link_form_icon('glyphicon-trash text-warning', $photo, 'DELETE', 'Delete the photo') !!}
				@if ($photo->is_primary)
				{!! link_form_icon('glyphicon-star text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
				@else
				{!! link_form_icon('glyphicon-star-empty text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
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
<script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/dropzone.js"></script>
<script>
Dropzone.autoDiscover = false;
$(document).ready(function(){

	var myDropzone = new Dropzone('#myDropzone', {
   		dictDefaultMessage: "Drop a file here to add an entity profile picture. Click the star to set primary photo."
	});

	$('div.dz-default.dz-message > span').show(); // Show message span
	$('div.dz-default.dz-message').css({'color': '#000000', 'opacity':1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 3,
		accept: ['.jpg','.png','.gif'],
		dictDefaultMessage: "Drop a file here to add a picture.  Click the start to set default.",
		init: function () {
	            myDropzone.on("complete", function (file) {
	                location.href = 'entities/{{ $entity->id }}'
	                location.reload();

	            });
	        }
	};

	myDropzone.options.addPhotosForm.init();
	
})
</script>
<script type="text/javascript">
    $('input.delete').on('click', function(e){
        e.preventDefault();
        var form = $(this).parents('form');
        swal({
                title: "Are you sure?",
                text: "You will not be able to recover this entity!",
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
