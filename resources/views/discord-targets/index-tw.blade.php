@extends('layouts.app-tw')

@section('title', 'Discord Targets')

@section('content')

<div class="container mx-auto">
	<!-- Page Header -->
	<div class="flex justify-between items-center mb-6">
		<div>
			<h1 class="text-3xl font-bold text-primary">Discord Targets</h1>
			<p class="text-muted-foreground mt-1">Channels that events are automatically reposted to.</p>
		</div>
		<a href="{{ route('discord-targets.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
			<i class="bi bi-plus-circle mr-2"></i>
			Add Target
		</a>
	</div>

	@unless($enabled)
		<div class="card-tw mb-6 border-l-4 border-destructive">
			<div class="p-4 flex items-start gap-3">
				<i class="bi bi-exclamation-triangle text-destructive text-lg"></i>
				<div>
					<p class="font-medium text-foreground">Discord posting is switched off</p>
					<p class="text-sm text-muted-foreground mt-1">
						Targets can be configured, but nothing will be sent until <code>DISCORD_ENABLED=true</code>
						is set in the environment.
					</p>
				</div>
			</div>
		</div>
	@endunless

	<!-- Filters -->
	<div class="card-tw mb-6">
		<div class="p-4">
			<form action="{{ route('discord-targets.index') }}" method="GET">
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
					<div>
						<label for="filter_name" class="block text-sm font-medium text-foreground mb-1">Name</label>
						<input type="text" name="name" id="filter_name" value="{{ $filters['name'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
					</div>
					<div>
						<label for="filter_enabled" class="block text-sm font-medium text-foreground mb-1">Status</label>
						<select name="enabled" id="filter_enabled" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
							<option value="">Any</option>
							<option value="1" {{ ($filters['enabled'] ?? '') === '1' ? 'selected' : '' }}>Enabled</option>
							<option value="0" {{ ($filters['enabled'] ?? '') === '0' ? 'selected' : '' }}>Disabled</option>
						</select>
					</div>
					<div>
						<label for="filter_mode" class="block text-sm font-medium text-foreground mb-1">Mode</label>
						<select name="mode" id="filter_mode" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
							<option value="">Any</option>
							@foreach(['create' => 'On creation', 'reminder' => 'Reminders', 'digest' => 'Weekly digest', 'manual' => 'Manual'] as $value => $label)
								<option value="{{ $value }}" {{ ($filters['mode'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="flex gap-2">
					<button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
						Apply Filters
					</button>
					<a href="{{ route('discord-targets.index') }}" class="px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
						Reset
					</a>
				</div>
			</form>
		</div>
	</div>

	@include('discord-targets.list-tw', ['targets' => $targets])

	<div class="mt-4">
		{!! $targets->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>
</div>

@stop
