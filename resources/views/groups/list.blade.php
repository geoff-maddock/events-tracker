<div class="panel">
<table class="table">
	<thead>
	<!-- CONVERT TO PARTIAL THAT BUILDS THE HEADERS FROM A CONFIGURED ARRAY OR ARRAY FROM DB - SEE MY WORK NOTES -->
	<tr class="bg-info">
		<th><a href="?sort_by=id&sort_order={{ $direction == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort_by=name&sort_order={{ $direction == 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort_by=label&sort_order={{ $direction == 'desc' ? 'asc' : 'desc' }}">Label</a></th>
		<th><a href="?sort_by=level&sort_order={{ $direction == 'desc' ? 'asc' : 'desc' }}">Level</a></th>
		<th style="width: 60px"></th>
	</tr>
	</thead>
@if (isset($groups) && count($groups) > 0)

@foreach ($groups as $group)
	<tr>
		<td>{!! $group->id !!}</td>
		<td>{!! link_to_route('groups.show', $group->name, [$group->id], ['class' => 'item-title']) !!}</td>
		<td>{!! $group->label !!}</td>
		<td>{!! $group->level !!}</td>
		<td>
			@can('edit_group')
			<a href="{!! route('groups.edit', ['group' => $group->id]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
      		{!! link_form_icon('glyphicon-trash text-warning', $group, 'DELETE', 'Delete the group') !!}
			@endcan
		</td>

	</tr>
	@endforeach
@else
	<tr>
		<td colspan="5">
			<i>No groups listed</i>
		</td>
	</tr>
@endif
</table>
</div>

@section('scripts.footer')
@stop
