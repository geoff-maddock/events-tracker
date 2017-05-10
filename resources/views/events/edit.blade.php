@extends('app')

@section('title','Event Edit')

@section('content')

<h1>Event . EDIT 
	@include('events.crumbs', ['slug' => $event->slug ? $event->slug : $event->id])
</h1>

	{!! Form::model($event, ['route' => ['events.update', $event->id], 'method' => 'PATCH']) !!}

		@include('events.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['events.destroy', $event->id]) !!}</P>

	<P><a href="{!! URL::route('events.index') !!}" class="btn btn-info">Return to list</a></P>
@stop

@section('scripts.footer')
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