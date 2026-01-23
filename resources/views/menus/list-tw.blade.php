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
						<a href="?sort=menu_parent_id&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Parent</a>
					</th>
					<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">
						<a href="?sort=visibility_id&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Visibility</a>
					</th>
					<th class="px-4 py-3 text-right text-sm font-semibold text-foreground">Actions</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-border">
				@if (isset($menus) && count($menus) > 0)
					@foreach ($menus as $menu)
						<tr class="hover:bg-muted/50 transition-colors">
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $menu->id }}</td>
							<td class="px-4 py-3">
								<a href="{{ route('menus.show', $menu->id) }}" class="text-primary hover:underline font-medium">
									{{ $menu->name }}
								</a>
							</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $menu->slug }}</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $menu->menuParent ? $menu->menuParent->name : '' }}</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $menu->visibility ? $menu->visibility->name : '' }}</td>
							<td class="px-4 py-3 text-right">
								<div class="flex items-center justify-end gap-2">
									@can('edit_menu')
										<a href="{{ route('menus.edit', $menu->id) }}" class="p-2 hover:bg-muted rounded transition-colors" title="Edit">
											<i class="bi bi-pencil text-primary"></i>
										</a>
										<form action="{{ route('menus.destroy', $menu->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this menu?');">
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
						<td colspan="6" class="px-4 py-8 text-center text-muted-foreground italic">
							No menus listed
						</td>
					</tr>
				@endif
			</tbody>
		</table>
	</div>
</div>
