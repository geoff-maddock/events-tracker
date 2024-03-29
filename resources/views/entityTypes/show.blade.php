@extends('app')

@section('title','Entity Type View')

@section('content')


<h1 class="display-6 text-primary">Entity Type	@include('entityTypes.crumbs', ['slug' => $entityType->label])</h4>

<div id="action-menu" class="mb-2">
	@can('edit_entityType')
		<a href="{!! route('entity-types.edit', ['entity_type' => $entityType->id]) !!}" class="btn btn-primary">Edit Entity Type</a>
	@endcan
	<a href="{!! URL::route('entity-types.index') !!}" class="btn btn-info">Return to list</a>
</div>

<div class="row">
	<div class="col-lg-6">
		<div class="event-card">
	<h2 class='item-title'>{{ $entityType->label }}</h2>

	<p>

	@if ($entityType->name)
	<label>Name: </label> <i>{{ $entityType->name }} </i><br>
	@endif

	@if ($entityType->slug)
	<label>Slug: </label> <i>{{ $entityType->slug }} </i><br>
	@endif


	@can('edit_entityType')
		{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $entityType, 'DELETE', 'Delete the [entityType]') !!}
	@endcan
  </div>


@stop

@section('scripts.footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  Swal.fire({
    title: "Are you sure?",
    text: "You will not be able to recover this entityType!",
    type: "warning",
    showCancelButton: true,
    confirmButtonColor: "#DD6B55",
    confirmButtonText: "Yes, delete it!",
    closeOnConfirm: true
  },
   function(isConfirm){
   	if (isConfirm) {
    	form.submit();
   	};
  });
})
</script>
@stop
