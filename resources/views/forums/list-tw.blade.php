<div class="card-tw overflow-hidden">
	<div class="overflow-x-auto">
		@if (count($forums) > 0)
			<table class="w-full">
				<thead class="bg-muted border-b border-border">
					<tr>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">Name</th>
						<th class="px-4 py-3 text-center text-sm font-semibold text-foreground">Threads</th>
						<th class="px-4 py-3 text-center text-sm font-semibold text-foreground">Views</th>
						<th class="px-4 py-3 text-center text-sm font-semibold text-foreground">Last Post</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-border">
					@foreach ($forums as $forum)
						<tr class="hover:bg-muted/50 transition-colors">
							<td class="px-4 py-3">
								<a href="{{ route('forums.show', $forum->id) }}" class="text-primary hover:underline font-medium">
									{{ $forum->name }}
								</a>
								@if ($signedIn && $forum->ownedBy($user))
									<div class="inline-flex items-center gap-2 ml-2">
										<a href="{{ route('forums.edit', $forum->id) }}" class="text-primary hover:text-primary/80" title="Edit">
											<i class="bi bi-pencil text-sm"></i>
										</a>
										<form action="{{ route('forums.destroy', $forum->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this forum?');">
											@csrf
											@method('DELETE')
											<button type="submit" class="text-destructive hover:text-destructive/80" title="Delete">
												<i class="bi bi-trash text-sm"></i>
											</button>
										</form>
									</div>
								@endif
							</td>
							<td class="px-4 py-3 text-center text-sm text-muted-foreground">{{ $forum->threadsCount ?? 0 }}</td>
							<td class="px-4 py-3 text-center text-sm text-muted-foreground">0</td>
							<td class="px-4 py-3 text-center text-sm text-muted-foreground"></td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@else
			<div class="px-4 py-8 text-center text-muted-foreground italic">
				No forums listed
			</div>
		@endif
	</div>
</div>
