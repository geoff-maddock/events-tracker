@extends('app')

@section('title','Menu View')

@section('content')


<h1>Menu
	@include('menus.crumbs', ['slug' => $menu->label])
</h1>

<P>
@can('edit_menu')
	<a href="{!! route('menus.edit', ['menu' => $menu->id]) !!}" class="btn btn-primary">Edit Menu</a>
@endcan
	<a href="{!! URL::route('menus.index') !!}" class="btn btn-info">Return to list</a>
</P>

<div class="row">
	<div class="profile-card col-md-4">
	<h2 class='item-title'>{{ $menu->label }}</h2>

	<p>

	@if ($menu->name)
	<label>Name: </label> <i>{{ $menu->name }} </i><br><br>
	@endif

	@if ($menu->slug)
	<label>Slug: </label> <i>{{ $menu->slug }} </i><br><br>
	@endif


	@can('edit_menu')
		{!! link_form_icon('glyphicon-trash text-warning', $menu, 'DELETE', 'Delete the [menu]') !!}
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
    text: "You will not be able to recover this menu!",
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
