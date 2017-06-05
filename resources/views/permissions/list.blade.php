<div class="panel">
<table class="table">
	<thead>
	<!-- CONVERT TO PARTIAL THAT BUILDS THE HEADERS FROM A CONFIGURED ARRAY OR ARRAY FROM DB - SEE MY WORK NOTES -->
	<tr class="bg-info">
		<th><a href="?sort_by=id&sort_direction={{ $sortDirection == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort_by=name&sort_direction={{ $sortDirection == 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort_by=label&sort_direction={{ $sortDirection == 'desc' ? 'asc' : 'desc' }}">Label</a></th>
		<th><a href="?sort_by=level&sort_direction={{ $sortDirection == 'desc' ? 'asc' : 'desc' }}">Level</a></th>
		<th style="width: 60px"></th>
	</tr>
	</thead>
@if (isset($permissions) && count($permissions) > 0)

@foreach ($permissions as $permission)
	<tr>
		<td>{!! $permission->id !!}</td>
		<td>{!! link_to_route('permissions.show', $permission->name, [$permission->id], ['class' => 'item-title']) !!}</td>
		<td>{!! $permission->label !!}</td>
		<td>{!! $permission->level !!}</td>
		<td>
			@can('edit_permission')
			<a href="{!! route('permissions.edit', ['id' => $permission->id]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
      		{!! link_form_icon('glyphicon-trash text-warning', $permission, 'DELETE', 'Delete the permission') !!}
			@endcan
		</td>

	</tr>		
	@endforeach
@else
	<tr>
		<td colspan="5">
			<i>No permissions listed</i>
		</td>
	</tr> 
@endif
</table>
</div>

@section('scripts.footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  swal({   
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
