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
						<a href="?sort=created_at&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}" class="hover:text-primary">Created At</a>
					</th>
					<th class="px-4 py-3 text-right text-sm font-semibold text-foreground">Actions</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-border">
				@if (isset($blogs) && count($blogs) > 0)
					@foreach ($blogs as $blog)
						<tr class="hover:bg-muted/50 transition-colors">
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $blog->id }}</td>
							<td class="px-4 py-3">
								<a href="{{ route('blogs.show', $blog->slug) }}" class="text-primary hover:underline font-medium">
									{{ $blog->name }}
								</a>
							</td>
							<td class="px-4 py-3 text-sm text-foreground">{{ $blog->slug }}</td>
							<td class="px-4 py-3 text-sm text-muted-foreground">{{ $blog->created_at->format('M j, Y') }}</td>
							<td class="px-4 py-3 text-right">
								<div class="flex items-center justify-end gap-2">
									@can('edit_blog')
										<a href="{{ route('blogs.edit', $blog->slug) }}" class="p-2 hover:bg-muted rounded transition-colors" title="Edit">
											<i class="bi bi-pencil text-primary"></i>
										</a>
									@endcan
								</div>
							</td>
						</tr>
					@endforeach
				@else
					<tr>
						<td colspan="5" class="px-4 py-8 text-center text-muted-foreground italic">
							No blogs listed
						</td>
					</tr>
				@endif
			</tbody>
		</table>
	</div>
</div>
