<div class="card-tw overflow-hidden">
	<div class="overflow-x-auto">
		<table class="w-full">
			<thead class="bg-muted border-b border-border">
				<tr>
					<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">
						<a href="?sort=id&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">ID</a>
					</th>
					<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">
						<a href="?sort=name&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Name</a>
					</th>
					<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">
						<a href="?sort=label&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Label</a>
					</th>
					<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">
						<a href="?sort=level&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Level</a>
					</th>
					<th class="px-4 py-3 text-right text-sm font-semibold text-foreground">Actions</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-border">
				@if (isset($groups) && count($groups) > 0)
					@foreach ($groups as $group)
						<tr class="hover:bg-muted/50 transition-colors">
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $group->id }}</td>
							<td class="px-4 py-3">
								<a href="{{ route('groups.show', $group->id) }}" class="text-primary hover:underline font-medium">
									{{ $group->name }}
								</a>
							</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $group->label }}</td>
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $group->level }}</td>
							<td class="px-4 py-3 text-right">
								<div class="flex items-center justify-end gap-2">
									@can('edit_group')
										<a href="{{ route('groups.edit', $group->id) }}" class="p-2 hover:bg-muted rounded transition-colors" title="Edit">
											<i class="bi bi-pencil text-primary"></i>
										</a>
										<form action="{{ route('groups.destroy', $group->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this group?');">
											@csrf
											@method('DELETE')
											<button type="submit" class="p-2 hover:bg-muted rounded transition-colors" title="Delete">
												<i class="bi bi-trash text-destructive"></i>
											</button>
										</form>
									@endcan
								</div>
							</td>
						</tr>
					@endforeach
				@else
					<tr>
						<td colspan="5" class="px-4 py-8 text-center text-muted-foreground italic">
							No groups listed
						</td>
					</tr>
				@endif
			</tbody>
		</table>
	</div>
</div>
