@extends('app')

@section('title','Group View')

@section('content')


<h1>Group
	@include('groups.crumbs', ['slug' => $group->label])
</h1>

<P>
@can('edit_group')
	<a href="{!! route('groups.edit', ['id' => $group->id]) !!}" class="btn btn-primary">Edit Group</a>
@endcan
	<a href="{!! URL::route('groups.index') !!}" class="btn btn-info">Return to list</a>
</P>

<div class="row">
	<div class="profile-card col-md-4">
	<h2 class='item-title'>{{ $group->label }}</h2>

	<p>

	@if ($group->name)
	<label>Name: </label> <i>{{ $group->name }} </i><br><br>
	@endif 

	@if ($group->level)
	<label>Level: </label> <i>{{ $group->level }} </i><br><br>
	@endif 

	@unless ($group->permissions->isEmpty())
		
		<P><b>Permissions:</b>
		
		@foreach ($group->permissions as $permission)
		<span class="label label-tag"><a href="/permissions/{{ $permission->id }}">{{ $permission->name }}</a></span>
		@endforeach

	@endunless

	@unless ($group->users->isEmpty())
		
		<P><b>Users:</b>
		
		@foreach ($group->users as $user)
		<span class="label label-tag"><a href="/users/{{ $user->id }}">{{ $user->name }}</a></span>
		@endforeach

	@endunless

	@can('edit_group')
		{!! link_form_icon('glyphicon-trash text-warning', $group, 'DELETE', 'Delete the [group]') !!}
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
    text: "You will not be able to recover this group!", 
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
