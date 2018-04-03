@extends('app')

@section('title','Event Edit')

@section('content')
<script src="{{ asset('/js/facebook-sdk.js') }}"></script>

<h2>Event . EDIT
	@include('events.crumbs', ['slug' => $event->slug ?: $event->id])<br>
  <a href="{!! route('events.show', ['id' => $event->id]) !!}" class="btn btn-primary">Show Event</a>

  @if (!empty($event->threads) && $user && (Auth::user()->id === $event->user->id || $user->id === Config::get('app.superuser')) )
    <a href="{!! route('events.createThread', ['id' => $event->id]) !!}" title="Create an thread related to this event." class="btn btn-primary"><span class='glyphicon glyphicon-comment'></span> Create Thread</a>
  @endif

  <a href="{!! URL::route('events.index') !!}" class="btn btn-info">Return to list</a>
</h2>

<div class="row">
  <div class="col-md-8">
	{!! Form::model($event, ['route' => ['events.update', $event->id], 'method' => 'PATCH']) !!}

		@include('events.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['events.destroy', $event->id]) !!}</P>

  </div>

  <div class="col-md-4">
    @if ($user && (Auth::user()->id === $event->user->id || $user->id === Config::get('app.superuser') ) )
    <form action="/events/{{ $event->id }}/photos" class="dropzone" id="myDropzone" method="POST">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
    </form>
    @endif

    <br style="clear: left;"/>
      <div id="api-show"></div>
    @foreach ($event->photos->chunk(4) as $set)
    <div class="row">
    @foreach ($set as $photo)
      <div class="col-md-2">
      <a href="/{{ $photo->path }}" data-lightbox="{{ $photo->path }}"><img src="/{{ $photo->thumbnail }}" alt="{{ $event->name}}"  style="max-width: 100%;"></a>
      @if ($user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )  
      @if ($signedIn || $user->id == Config::get('app.superuser')) 
        {!! link_form_icon('glyphicon-trash text-warning', $photo, 'DELETE', 'Delete the photo') !!}
        @if ($photo->is_primary)
        {!! link_form_icon('glyphicon-star text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
        @else
        {!! link_form_icon('glyphicon-star-empty text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
        @endif
      @endif
      @endif
      </div>
    @endforeach
    </div>
    @endforeach
    </div>

  </div>

</div>
@stop

@section('scripts.footer')
<script src="{{ asset('/js/facebook-event.js') }}"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/dropzone.js"></script>
<script>
Dropzone.autoDiscover = false;
$(document).ready(function(){

  var myDropzone = new Dropzone('#myDropzone', {
      dictDefaultMessage: "Drop a file here to add an event image."
  });

  $('div.dz-default.dz-message > span').show(); // Show message span
  $('div.dz-default.dz-message').css({'color': '#000000','opacity':1, 'background-image': 'none'});
  
  myDropzone.options.addPhotosForm = {
    maxFilesize: 3,
    accept: ['.jpg','.png','.gif'],
    init: function () {
              myDropzone.on("complete", function (file) {
                  location.href = 'events/{{ $event->id }}'
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
    text: "You will not be able to recover this event!", 
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