@extends('layouts.app-tw')

@section('title', $target->name)

@section('content')

<div class="max-w-5xl mx-auto">
	<!-- Header -->
	<div class="flex justify-between items-start mb-6">
		<div>
			<h1 class="text-3xl font-bold text-primary">{{ $target->name }}</h1>
			<p class="text-muted-foreground font-mono text-sm mt-1">{{ $target->maskedUrl() }}</p>
		</div>
		<div class="flex items-center gap-2">
			<form action="{{ route('discord-targets.test', ['discordTarget' => $target->id]) }}" method="POST">
				@csrf
				<button type="submit" class="px-4 py-2 bg-muted text-foreground rounded-lg hover:bg-muted/80 transition-colors">
					<i class="bi bi-send mr-2"></i>Send test message
				</button>
			</form>
			<form action="{{ route('discord-targets.toggle', ['discordTarget' => $target->id]) }}" method="POST">
				@csrf
				<button type="submit" class="px-4 py-2 bg-muted text-foreground rounded-lg hover:bg-muted/80 transition-colors">
					{{ $target->is_enabled ? 'Disable' : 'Enable' }}
				</button>
			</form>
			<a href="{{ route('discord-targets.edit', ['discordTarget' => $target->id]) }}" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
				<i class="bi bi-pencil mr-2"></i>Edit
			</a>
		</div>
	</div>

	@unless($enabled)
		<div class="card-tw mb-6 border-l-4 border-destructive">
			<div class="p-4 text-sm text-muted-foreground">
				Discord posting is switched off site-wide (<code>DISCORD_ENABLED</code>). Nothing will be sent.
			</div>
		</div>
	@endunless

	@if(! $target->is_enabled && $target->disabled_reason)
		<div class="card-tw mb-6 border-l-4 border-destructive">
			<div class="p-4">
				<p class="font-medium text-foreground">This target is disabled</p>
				<p class="text-sm text-muted-foreground mt-1">{{ $target->disabled_reason }}</p>
				<p class="text-sm text-muted-foreground mt-1">
					If the webhook was deleted in Discord, create a new one and paste the new URL into
					<a href="{{ route('discord-targets.edit', ['discordTarget' => $target->id]) }}" class="text-primary hover:underline">the edit form</a>.
				</p>
			</div>
		</div>
	@endif

	<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
		<!-- Configuration -->
		<div class="card-tw">
			<div class="p-6">
				<h2 class="text-lg font-semibold text-foreground mb-4">Configuration</h2>
				<dl class="space-y-3 text-sm">
					<div class="flex justify-between gap-4">
						<dt class="text-muted-foreground">Status</dt>
						<dd class="text-foreground">{{ $target->is_enabled ? 'Enabled' : 'Disabled' }}</dd>
					</div>
					<div class="flex justify-between gap-4">
						<dt class="text-muted-foreground">Receives</dt>
						<dd class="text-foreground text-right">
							@if($target->match_all)
								All public events
							@else
								{{ $target->criteria->where('mode', 'include')->count() }} include,
								{{ $target->criteria->where('mode', 'exclude')->count() }} exclude
							@endif
						</dd>
					</div>
					<div class="flex justify-between gap-4">
						<dt class="text-muted-foreground">Requires a flyer</dt>
						<dd class="text-foreground">{{ $target->requires_photo ? 'Yes' : 'No' }}</dd>
					</div>
					<div class="flex justify-between gap-4">
						<dt class="text-muted-foreground">On creation</dt>
						<dd class="text-foreground">{{ $target->post_on_create ? 'Yes' : 'No' }}</dd>
					</div>
					<div class="flex justify-between gap-4">
						<dt class="text-muted-foreground">Reminders</dt>
						<dd class="text-foreground">
							{{ $target->post_reminders ? implode(', ', $target->effectiveReminderOffsets()) . ' days before' : 'No' }}
						</dd>
					</div>
					<div class="flex justify-between gap-4">
						<dt class="text-muted-foreground">Weekly digest</dt>
						<dd class="text-foreground">
							@if($target->post_digest)
								{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$target->digest_day] }}
								at {{ sprintf('%02d:00', $target->digest_hour) }}
							@else
								No
							@endif
						</dd>
					</div>
					<div class="flex justify-between gap-4">
						<dt class="text-muted-foreground">Last success</dt>
						<dd class="text-foreground">{{ $target->last_success_at?->diffForHumans() ?? 'Never' }}</dd>
					</div>
				</dl>

				@if($target->criteria->isNotEmpty())
					<div class="mt-4 pt-4 border-t border-border">
						<h3 class="text-sm font-medium text-foreground mb-2">Filters</h3>
						<div class="flex flex-wrap gap-2">
							@foreach($target->criteria as $criterion)
								<span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $criterion->isExclude() ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary' }}">
									{{ $criterion->isExclude() ? '−' : '+' }} {{ $criterion->typeLabel() }}: {{ $criterion->label() }}
								</span>
							@endforeach
						</div>
					</div>
				@endif
			</div>
		</div>

		<!-- Preview -->
		<div class="card-tw">
			<div class="p-6">
				<h2 class="text-lg font-semibold text-foreground mb-1">Would receive next</h2>
				<p class="text-xs text-muted-foreground mb-4">
					Upcoming events matching this target's filters right now — the quickest way to check the filters do what you expect.
				</p>
				@forelse($upcoming as $event)
					<div class="py-2 border-b border-border last:border-0">
						<a href="{{ route('events.show', $event) }}" class="text-primary hover:underline">{{ $event->name }}</a>
						<div class="text-xs text-muted-foreground">
							{{ $event->start_at?->format('D M j') }}
							@if($event->venue) · {{ $event->venue->name }} @endif
						</div>
					</div>
				@empty
					<p class="text-sm text-muted-foreground">
						No upcoming events match. Check the include filters — a target with none receives nothing.
					</p>
				@endforelse
			</div>
		</div>
	</div>

	<!-- Recent posts -->
	<div class="card-tw">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-foreground mb-4">Recent posts</h2>
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="border-b border-border">
						<tr class="text-left text-muted-foreground">
							<th class="px-2 py-2 font-medium">When</th>
							<th class="px-2 py-2 font-medium">Event</th>
							<th class="px-2 py-2 font-medium">Reason</th>
							<th class="px-2 py-2 font-medium">Status</th>
						</tr>
					</thead>
					<tbody>
						@forelse($posts as $post)
							<tr class="border-b border-border last:border-0">
								<td class="px-2 py-2 text-muted-foreground whitespace-nowrap">{{ $post->created_at?->diffForHumans() }}</td>
								<td class="px-2 py-2">
									@if($post->event)
										<a href="{{ route('events.show', $post->event) }}" class="text-primary hover:underline">{{ $post->event->name }}</a>
									@else
										<span class="text-muted-foreground">—</span>
									@endif
								</td>
								<td class="px-2 py-2 text-muted-foreground">
									{{ ucfirst($post->reason) }}{{ $post->reason_key ? ' (' . $post->reason_key . ')' : '' }}
								</td>
								<td class="px-2 py-2">
									@if($post->isSent())
										<span class="text-primary">Sent</span>
									@elseif($post->status === 'failed')
										<span class="text-destructive" title="{{ $post->error }}">Failed</span>
									@else
										<span class="text-muted-foreground">{{ ucfirst($post->status) }}</span>
									@endif
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="4" class="px-2 py-6 text-center text-muted-foreground">Nothing posted yet.</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="mt-4">
		<a href="{{ route('discord-targets.index') }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
			<i class="bi bi-arrow-left"></i>
			<span>Return to list</span>
		</a>
	</div>
</div>

@stop
