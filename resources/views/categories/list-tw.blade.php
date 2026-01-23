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
						<a href="?sort=slug&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Forum</a>
					</th>
					<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">
						<a href="?sort=created_at&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Created At</a>
					</th>
					<th class="px-4 py-3 text-right text-sm font-semibold text-foreground">Actions</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-border">
				@if (isset($categories) && count($categories) > 0)
					@foreach ($categories as $category)
						<tr class="hover:bg-muted/50 transition-colors">
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $category->id }}</td>
							<td class="px-4 py-3">
								<a href="{{ route('categories.show', $category->id) }}" class="text-primary hover:underline font-medium">
									{{ $category->name }}
								</a>
							</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $category->forum->name }}</td>
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $category->created_at->format('M j, Y') }}</td>
							<td class="px-4 py-3 text-right">
								<div class="flex items-center justify-end gap-2">
									@can('edit_category')
										<a href="{{ route('categories.edit', $category->id) }}" class="p-2 hover:bg-muted rounded transition-colors" title="Edit">
											<i class="bi bi-pencil text-primary"></i>
										</a>
										<form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
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
							No categories listed
						</td>
					</tr>
				@endif
			</tbody>
		</table>
	</div>
</div>
