@extends('layouts.app-tw')

@section('title', 'Entity Claims')

@section('content')

<div class="container mx-auto">
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary">Entity Claims</h1>
		<p class="text-muted-foreground mt-1">
			Requests to take over an entity page. Approving makes the claimant the only owner and removes everyone who owned it before.
		</p>
	</div>

	<h2 class="text-xl font-semibold text-foreground mb-3">Pending ({{ $pending->count() }})</h2>

	@forelse ($pending as $claim)
	<div class="card-tw mb-4">
		<div class="p-4 space-y-3">
			<div class="flex flex-wrap items-baseline justify-between gap-2">
				<div>
					<a href="{{ route('entities.show', $claim->entity) }}" class="font-semibold text-primary hover:underline">{{ $claim->entity->name }}</a>
					<span class="text-muted-foreground">claimed by</span>
					<a href="{{ url('/users/'.$claim->user->id) }}" class="font-medium text-foreground hover:underline">{{ $claim->user->name }}</a>
					<span class="text-sm text-muted-foreground">({{ $claim->user->email }})</span>
				</div>
				<span class="text-sm text-muted-foreground">{{ $claim->created_at->diffForHumans() }}</span>
			</div>

			<p class="text-foreground whitespace-pre-line">{{ $claim->message }}</p>

			@if ($claim->evidence_url)
			<p class="text-sm break-all">
				<i class="bi bi-link-45deg"></i>
				<a href="{{ $claim->evidence_url }}" class="text-primary hover:underline" target="_blank" rel="noopener noreferrer nofollow">{{ $claim->evidence_url }}</a>
			</p>
			@endif

			<p class="text-sm text-muted-foreground">
				Current owners:
				{{ $claim->entity->owners->isEmpty() ? 'none' : $claim->entity->owners->pluck('name')->implode(', ') }}
			</p>

			<div class="flex flex-col md:flex-row gap-3">
				<form action="{{ route('entity-claims.approve', $claim) }}" method="POST" class="flex-1 flex gap-2"
					data-confirm="Make {{ $claim->user->name }} the only owner of {{ $claim->entity->name }}?">
					@csrf
					<x-ui.input name="review_note" placeholder="Note to claimant (optional)" maxlength="1000" />
					<x-ui.button type="submit" variant="default">Approve</x-ui.button>
				</form>
				<form action="{{ route('entity-claims.deny', $claim) }}" method="POST" class="flex-1 flex gap-2">
					@csrf
					<x-ui.input name="review_note" placeholder="Reason (optional, emailed)" maxlength="1000" />
					<x-ui.button type="submit" variant="destructive">Deny</x-ui.button>
				</form>
			</div>
		</div>
	</div>
	@empty
	<div class="card-tw mb-4">
		<div class="p-4 text-muted-foreground">No claims are waiting for review.</div>
	</div>
	@endforelse

	@if ($recent->isNotEmpty())
	<h2 class="text-xl font-semibold text-foreground mt-8 mb-3">Recently decided</h2>
	<div class="card-tw overflow-x-auto">
		<table class="w-full text-sm">
			<thead>
				<tr class="text-left text-muted-foreground border-b border-border">
					<th class="p-3">Entity</th>
					<th class="p-3">Claimant</th>
					<th class="p-3">Status</th>
					<th class="p-3">Reviewed by</th>
					<th class="p-3">When</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($recent as $claim)
				<tr class="border-b border-border last:border-0">
					<td class="p-3"><a href="{{ route('entities.show', $claim->entity) }}" class="text-primary hover:underline">{{ $claim->entity->name }}</a></td>
					<td class="p-3">{{ $claim->user->name }}</td>
					<td class="p-3">{{ ucfirst($claim->status) }}</td>
					<td class="p-3">{{ $claim->reviewer?->name ?? '—' }}</td>
					<td class="p-3 text-muted-foreground">{{ ($claim->reviewed_at ?? $claim->updated_at)->diffForHumans() }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	@endif
</div>

@stop
