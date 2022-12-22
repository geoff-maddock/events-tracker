@extends('app')

@section('title', 'Event Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<script src="{{ asset('/js/facebook-sdk.js') }}"></script>
<script async defer src="https://connect.facebook.net/en_US/sdk.js"></script>

<h1 class="display-6 text-primary">Events. Edit	@include('events.crumbs', ['slug' => $event->slug ?: $event->id])</h1>

<div id="action-menu" class="mb-2">
  <a href="{!! route('events.show', ['event' => $event->id]) !!}" class="btn btn-primary">Show Event</a>

  @if (!empty($event->threads) && $user && (Auth::user()->id === $event->user->id || $user->id === Config::get('app.superuser')) )
    <a href="{!! route('events.createThread', ['id' => $event->id]) !!}" title="Create an thread related to this event." class="btn btn-primary"><i class="bi bi-chat-text-fill"></i> Create Thread</a>
  @endif

  <a href="{!! URL::route('events.index') !!}" class="btn btn-info">Return to list</a>
</div>

<div class="row">
  <div class="col-md-8">
	{!! Form::model($event, ['route' => ['events.update', $event->id], 'method' => 'PATCH', 'class' => 'form-container']) !!}

		@include('events.form', ['action' => 'update',])

	{!! Form::close() !!}

	<P>{!! delete_form(['events.destroy', $event->id]) !!}</P>

  </div>

  <div class="col-md-4">
    <div class="row">
      @foreach ($event->photos->chunk(4) as $set)
        @foreach ($set as $photo)
          <div class="col-md-2">
          <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="{{ Storage::disk('external')->url($photo->getStoragePath()) }}">
            <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $event->name}}" class="mw-100"></a>
          @if ($user && (Auth::user()->id === $event->user->id || $user->id === Config::get('app.superuser')))
          @if ($signedIn || $user->id === Config::get('app.superuser'))
            {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $photo, 'DELETE', 'Delete the photo') !!}
            @if ($photo->is_primary)
            {!! link_form_bootstrap_icon('bi bi-star-fill text-primary icon', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
            @else
            {!! link_form_bootstrap_icon('bi bi-star text-info icon', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
            @endif
          @endif
          @endif
          </div>
        @endforeach
      @endforeach

      <div class="col mb-2">
      @if ($user && (Auth::user()->id === $event->user->id || $user->id === Config::get('app.superuser') || $event->canUserPostPhoto($user)) )
      <form action="/events/{{ $event->id }}/photos" class="dropzone" id="myDropzone" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
      </form>
      @endif
      </div>

      <div id="api-show"></div>
    </div>
  </div>
@stop

@section('scripts.footer')
<script src="{{ asset('/js/facebook-event.js') }}"></script>
<script>
    window.Dropzone["autoDiscover"] = false;

    $(document).ready(function(){

      var myDropzone = new window.Dropzone('#myDropzone', {
          dictDefaultMessage: "Drop a file here to add an event image. (Max size 5MB)"
      });

      $('div.dz-default.dz-message > span').show(); // Show message span
      $('div.dz-default.dz-message').css({'color': '#000000','opacity':1, 'background-image': 'none'});

      myDropzone.options.addPhotosForm = {
		maxFilesize: 5,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
				myDropzone.on("success", function (file) {
	                location.href = 'events/{{ $event->id }}';
	                location.reload();
	            });
        myDropzone.on("successmultiple", function (file) {
            location.href = 'events/{{ $event->id }}';
            location.reload();
        });
				myDropzone.on("error", function (file, message) {
					Swal.fire({
						title: "Are you sure?",
						text: "Error: " + message.message,
						type: "warning",
						showCancelButton: true,
						confirmButtonColor: "#DD6B55",
						confirmButtonText: "Ok",
				}).then(result => {
					location.href = 'events/{{ $event->id }}';
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
            text: "You will not be able to recover this event!",
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
