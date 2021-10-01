@extends('app')

@section('title','Permission View')

@section('content')

<h1 class="display-6 text-primary">Permission @include('permissions.crumbs', ['slug' => $permission->label])</h1>

<div id="action-menu" class="mb-2">
@can('edit_permission')
<a href="{!! route('permissions.edit', ['permission' => $permission->id]) !!}" class="btn btn-primary">Edit Permission</a>
@endcan
<a href="{!! URL::route('permissions.index') !!}" class="btn btn-info">Return to list</a>
</div>

<div class="row">
	<div class="col-lg-6">
	<div class="profile-card">
	<h2 class='item-title'>{{ $permission->label }}</h2>

	<p>

	@if ($permission->name)
	<label>Name: </label> <i>{{ $permission->name }} </i><br><br>
	@endif

	@if ($permission->level)
	<label>Level: </label> <i>{{ $permission->level }} </i><br><br>
	@endif

	@unless ($permission->groups->isEmpty())

		<P><b>Groups:</b>

		@foreach ($permission->groups as $group)
		<span class="label label-tag"><a href="/groups/{{ $group->id }}">{{ $group->name }}</a></span>
		@endforeach

	@endunless

	@can('edit_permission')
		{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $permission, 'DELETE', 'Delete the [permission]') !!}
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
    text: "You will not be able to recover this permission!",
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
