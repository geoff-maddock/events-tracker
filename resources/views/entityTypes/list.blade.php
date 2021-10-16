<div class="panel">
<table class="table">
	<thead>
	<tr class="bg-info">
		<th><a href="?sort=id&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort=name&direction={{ $direction = 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort=slug&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Slug</a></th>
        <th><a href="?sort=short&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Short</a></th>
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
			<a href="{!! route('entity-types.edit', ['entity_type' => $entityType->id]) !!}"><i class="bi bi-pencil"></i></a>
      		{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $entityType, 'DELETE', 'Delete the entityType') !!}
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

@stop
