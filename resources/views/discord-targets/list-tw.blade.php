<div class="card-tw">
	<div class="overflow-x-auto">
		<table class="w-full text-sm">
			<thead class="border-b border-border">
				<tr class="text-left text-muted-foreground">
					<th class="px-4 py-3 font-medium">Name</th>
					<th class="px-4 py-3 font-medium">Receives</th>
					<th class="px-4 py-3 font-medium">Modes</th>
					<th class="px-4 py-3 font-medium">Status</th>
					<th class="px-4 py-3 font-medium">Last post</th>
					<th class="px-4 py-3"></th>
				</tr>
			</thead>
			<tbody>
				@forelse($targets as $target)
					<tr class="border-b border-border hover:bg-accent/50 transition-colors">
						<td class="px-4 py-3">
							<a href="{{ route('discord-targets.show', ['discordTarget' => $target->id]) }}" class="font-medium text-primary hover:underline">
								{{ $target->name }}
							</a>
							<div class="text-xs text-muted-foreground font-mono">{{ $target->maskedUrl() }}</div>
						</td>
						<td class="px-4 py-3">
							@if($target->match_all)
								<span class="text-foreground">All events</span>
							@else
								<span class="text-muted-foreground">{{ $target->criteria->count() }} filter(s)</span>
							@endif
						</td>
						<td class="px-4 py-3 text-muted-foreground">
							@php
								$modes = collect([
									'New' => $target->post_on_create,
									'Reminders' => $target->post_reminders,
									'Digest' => $target->post_digest,
									'Manual' => $target->allow_manual,
								])->filter()->keys();
							@endphp
							{{ $modes->isEmpty() ? '—' : $modes->implode(', ') }}
						</td>
						<td class="px-4 py-3">
							@if($target->is_enabled)
								<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-primary/10 text-primary">Enabled</span>
							@else
								<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-destructive/10 text-destructive" title="{{ $target->disabled_reason }}">Disabled</span>
							@endif
						</td>
						<td class="px-4 py-3 text-muted-foreground">
							{{ $target->last_success_at?->diffForHumans() ?? 'Never' }}
						</td>
						<td class="px-4 py-3 text-right">
							<a href="{{ route('discord-targets.edit', ['discordTarget' => $target->id]) }}" class="text-muted-foreground hover:text-foreground" title="Edit">
								<i class="bi bi-pencil"></i>
							</a>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
							No Discord targets yet. Add one to start reposting events.
						</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
