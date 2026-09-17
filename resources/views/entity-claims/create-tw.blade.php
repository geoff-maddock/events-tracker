@extends('layouts.app-tw')

@section('title', 'Claim '.$entity->name)

@section('content')

<div class="max-w-3xl mx-auto">
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Claim {{ $entity->name }}</h1>
		<p class="text-muted-foreground">
			Run this page? Ask to take it over. Once an admin approves your claim, you become its owner:
			you can edit its details, links, locations, contacts and photos, and anyone who managed it before loses access.
		</p>
	</div>

	@if ($pendingClaim)
	<div class="card-tw">
		<div class="p-6 space-y-4">
			<div class="flex items-start gap-3">
				<i class="bi bi-hourglass-split text-primary text-xl"></i>
				<div>
					<p class="font-medium text-foreground">Your claim is waiting for review</p>
					<p class="text-sm text-muted-foreground mt-1">
						Sent {{ $pendingClaim->created_at->diffForHumans() }}. We'll email you when an admin decides.
					</p>
				</div>
			</div>
			<form action="{{ route('entity-claims.withdraw', $pendingClaim) }}" method="POST">
				@csrf
				<x-ui.button type="submit" variant="outline">Withdraw claim</x-ui.button>
			</form>
		</div>
	</div>
	@else
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('entities.claim.store', $entity) }}" method="POST" class="space-y-6">
				@csrf

				<x-ui.form-group
					name="message"
					label="How are you connected to {{ $entity->name }}?"
					:required="true"
					:error="$errors->first('message')"
					helpText="For example: you're the artist, you book the venue, or you run the promotion crew. Tell us how we can confirm it.">
					<x-ui.textarea
						name="message"
						id="message"
						rows="5"
						maxlength="2000"
						required
						:hasError="$errors->has('message')">{{ old('message') }}</x-ui.textarea>
				</x-ui.form-group>

				<x-ui.form-group
					name="evidence_url"
					label="Link that shows the connection (optional)"
					:error="$errors->first('evidence_url')"
					helpText="An official website, social profile, or Bandcamp page that lists your name or this email address.">
					<x-ui.input
						type="url"
						name="evidence_url"
						id="evidence_url"
						value="{{ old('evidence_url') }}"
						placeholder="https://"
						:hasError="$errors->has('evidence_url')" />
				</x-ui.form-group>

				<div class="flex items-center gap-4">
					<x-ui.button type="submit" variant="default">Send claim</x-ui.button>
					<a href="{{ route('entities.show', $entity) }}" class="text-sm text-muted-foreground hover:text-foreground">Cancel</a>
				</div>
			</form>
		</div>
	</div>
	@endif
</div>

@stop
