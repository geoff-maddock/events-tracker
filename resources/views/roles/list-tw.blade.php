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
						<a href="?sort=slug&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Slug</a>
					</th>
					<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">
						<a href="?sort=short&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Short</a>
					</th>
					<th class="px-4 py-3 text-right text-sm font-semibold text-foreground">Actions</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-border">
				@if (isset($roles) && count($roles) > 0)
					@foreach ($roles as $role)
						<tr class="hover:bg-muted/50 transition-colors">
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $role->id }}</td>
							<td class="px-4 py-3">
								<span class="text-foreground font-medium">
									{{ $role->name }}
								</span>
							</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $role->slug }}</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $role->short }}</td>
							<td class="px-4 py-3 text-right">
								<div class="flex items-center justify-end gap-2">
									<a href="{{ route('roles.edit', $role->id) }}" class="p-2 hover:bg-muted rounded transition-colors" title="Edit">
										<i class="bi bi-pencil text-primary"></i>
									</a>
									<form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
										@csrf
										@method('DELETE')
										<button type="submit" class="p-2 hover:bg-muted rounded transition-colors" title="Delete">
											<i class="bi bi-trash text-destructive"></i>
										</button>
									</form>
								</div>
							</td>
						</tr>
					@endforeach
				@else
					<tr>
						<td colspan="5" class="px-4 py-8 text-center text-muted-foreground italic">
							No roles listed
						</td>
					</tr>
				@endif
			</tbody>
		</table>
	</div>
</div>
