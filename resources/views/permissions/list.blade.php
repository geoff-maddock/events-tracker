<div class="panel">
<table class="table">
	<thead>
	<!-- CONVERT TO PARTIAL THAT BUILDS THE HEADERS FROM A CONFIGURED ARRAY OR ARRAY FROM DB - SEE MY WORK NOTES -->
	<tr class="bg-info">
		<th><a href="?sort=id&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort=name&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort=label&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Label</a></th>
		<th><a href="?sort=level&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Level</a></th>
		<th class="col-1"></th>
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
			<a href="{!! route('permissions.edit', ['permission' => $permission->id]) !!}"><i class="bi bi-pencil-fill icon"></i></a>
			{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $permission, 'DELETE', 'Delete the permission', NULL, 'delete') !!}
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