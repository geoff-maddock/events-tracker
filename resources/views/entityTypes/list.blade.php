<div class="panel">
<table class="table">
	<thead>
	<tr class="bg-info">
		<th><a href="?sort_by=id&sort_order={{ $sortOrder == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort_by=name&sort_order={{ $sortOrder == 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort_by=slug&sort_order={{ $sortOrder == 'desc' ? 'asc' : 'desc' }}">Slug</a></th>
        <th><a href="?sort_by=short&sort_order={{ $sortOrder == 'desc' ? 'asc' : 'desc' }}">Short</a></th>
		<th style="width: 60px"></th>
	</tr>
	</thead>
@if (isset($entityTypes) && count($entityTypes) > 0)

@foreach ($entityTypes as $entityType)
	<tr>
		<td>{!! $entityType->id !!}</td>
		<td>{!! link_to_route('entity-types.show', $entityType->name, [$entityType->id], ['class' => 'item-title']) !!}</td>
		<td>{!! $entityType->slug !!}</td>
        <td>{!! $entityType->short !!}</td>
		<td>
			@can('edit_entityType')
			<a href="{!! route('entity-types.edit', ['entity_type' => $entityType->id]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
      		{!! link_form_icon('glyphicon-trash text-warning', $entityType, 'DELETE', 'Delete the entityType') !!}
			@endcan
		</td>

	</tr>
	@endforeach
@else
	<tr>
		<td colspan="5">
			<i>No entitytypes listed</i>
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
