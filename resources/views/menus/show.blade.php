@extends('app')

@section('title','Menu View')

@section('content')


<h1 class="display-6 text-primary">Menu
	@include('menus.crumbs', ['slug' => $menu->label])
</h1>

<div id="action-menu" class="mb-2">
	@can('edit_menu')
	<a href="{!! route('menus.edit', ['menu' => $menu->id]) !!}" class="btn btn-primary">Edit Menu</a>
	@endcan
	<a href="{!! URL::route('menus.index') !!}" class="btn btn-info">Return to list</a>
</div>

<div class="row">
	<div class="col-lg-6">
		<div class="profile-card">
		<h2 class='item-title'>{{ $menu->label }}</h2>

		@if ($menu->name)
		<label>Name: </label> <i>{{ $menu->name }} </i><br>
		@endif

		@if ($menu->slug)
		<label>Slug: </label> <i>{{ $menu->slug }} </i><br>
		@endif

	@can('edit_menu')
		{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $menu, 'DELETE', 'Delete the [menu]') !!}
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
