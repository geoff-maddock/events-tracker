@extends('layouts.app-tw')

@section('title', $entity->name.' · Ticket link clicks')

@section('content')

<div class="container mx-auto max-w-6xl">
	<!-- Page Header -->
	<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
		<div>
			<a href="{{ route('entities.stats', ['entity' => $entity, 'period' => $period]) }}" class="text-sm text-primary hover:text-primary/90 inline-flex items-center gap-1 mb-2">
				<i class="bi bi-arrow-left"></i> {{ $entity->name }} stats
			</a>
			<h1 class="text-3xl font-bold text-primary">Ticket link clicks</h1>
			<p class="text-muted-foreground mt-1">
				Every recorded click on a ticket link for events {{ $entity->name }} is part of, over the last {{ $period }} days, including today.
				{{ number_format($counted) }} of {{ number_format($total) }} count in the stats.
			</p>
			<p class="text-xs text-muted-foreground mt-1">
				Not counted: clicks with no user agent, from bots or AI agents, or made after the event ended (its end time, or midnight after its start day if that is later). Bots that were recognised at the time were never recorded.
			</p>
		</div>

		<div class="inline-flex rounded-lg border border-border overflow-hidden" role="group" aria-label="Period">
			@foreach ($periods as $days)
			<a href="{{ route('entities.stats.clicks', ['entity' => $entity, 'period' => $days]) }}"
				class="px-4 py-2 text-sm {{ $days === $period ? 'bg-primary text-primary-foreground' : 'bg-card text-foreground hover:bg-accent' }}"
				@if ($days === $period) aria-current="page" @endif>
				Last {{ $days }} days
			</a>
			@endforeach
		</div>
	</div>

	<div class="card-tw overflow-hidden mb-6">
		<div class="overflow-x-auto">
			<table class="w-full">
				<thead class="bg-muted border-b border-border">
					<tr>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">Clicked</th>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">Event</th>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">Counted</th>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">User</th>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">Referrer</th>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">User agent</th>
						<th class="px-4 py-3 text-left text-sm font-semibold text-foreground">IP</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-border">
					@forelse ($clicks as $click)
					@php
						$reason = $click->exclusionReason();
					@endphp
					<tr class="hover:bg-muted/50 transition-colors align-top">
						<td class="px-4 py-3 text-sm text-foreground whitespace-nowrap">{{ $click->clicked_at?->format('M j, Y g:ia') }}</td>
						<td class="px-4 py-3 text-sm">
							@if ($click->event)
							<a href="{{ route('events.show', $click->event) }}" class="text-primary hover:underline">{{ $click->event->name }}</a>
							<div class="text-xs text-muted-foreground">{{ $click->event->start_at?->format('M j, Y g:ia') }}</div>
							@elseif ($click->event_id)
							<span class="text-muted-foreground">Deleted event #{{ $click->event_id }}</span>
							@else
							<span class="text-muted-foreground">Series ticket link</span>
							@endif
						</td>
						<td class="px-4 py-3 text-sm whitespace-nowrap">
							@if ($reason)
							<span class="text-destructive">No</span>
							<div class="text-xs text-muted-foreground">{{ $reason }}</div>
							@else
							<span class="text-green-600 dark:text-green-400">Yes</span>
							@endif
						</td>
						<td class="px-4 py-3 text-sm">
							@if ($click->user)
							<a href="{{ route('users.show', $click->user) }}" class="text-primary hover:underline">{{ $click->user->name }}</a>
							@else
							<span class="text-muted-foreground">Guest</span>
							@endif
						</td>
						<td class="px-4 py-3 text-xs text-muted-foreground break-all max-w-xs">{{ $click->referrer ?: '—' }}</td>
						<td class="px-4 py-3 text-xs text-muted-foreground break-all max-w-sm">{{ $click->user_agent ?: '(none)' }}</td>
						<td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{{ $click->ip_address ?: '—' }}</td>
					</tr>
					@empty
					<tr>
						<td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No ticket link clicks in the last {{ $period }} days.</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>

	{{ $clicks->links('vendor.pagination.tailwind') }}
</div>

@endsection
