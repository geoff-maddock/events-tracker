@extends('layouts.app-tw')

@section('title', 'All Modules')

@section('content')

<div class="container mx-auto max-w-6xl">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">All Modules</h1>
		<p class="text-muted-foreground">Browse all available sections and features</p>
	</div>

	<!-- Public Modules -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<h2 class="text-2xl font-bold text-foreground mb-4 flex items-center gap-2">
				<i class="bi bi-globe text-primary"></i>
				Public Modules
			</h2>
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
				@php
				$publicModules = config('modules.public', []);
				$notificationsUnread = 0;
				if (Auth::check()) {
					$notificationsUnread = Auth::user()->unreadNotifications()->count();
					$publicModules = array_merge($publicModules, config('modules.auth', []));
				}
				sort($publicModules);
				@endphp

				@foreach($publicModules as $module)
				<a href="{{ url($module['url']) }}" class="block p-4 bg-muted/50 hover:bg-muted rounded-lg border border-border hover:border-primary transition-all group">
					<div class="flex items-start gap-3">
						<div class="p-2 bg-primary/10 rounded-lg group-hover:bg-primary/20 transition-colors">
							<i class="{{ $module['icon'] }} text-primary text-xl"></i>
						</div>
						<div class="flex-1">
							<h3 class="font-semibold text-foreground group-hover:text-primary transition-colors">
								{{ $module['name'] }}
								@if($module['url'] === '/job-status' && $notificationsUnread > 0)
								<span class="ml-2 inline-flex items-center justify-center text-xs font-semibold rounded-full bg-red-600 text-white px-2 py-0.5">{{ $notificationsUnread }}</span>
								@endif
							</h3>
							<p class="text-sm text-muted-foreground mt-1">{{ $module['description'] }}</p>
						</div>
					</div>
				</a>
				@endforeach
			</div>
		</div>
	</div>

	<!-- Policies & Info -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<h2 class="text-2xl font-bold text-foreground mb-4 flex items-center gap-2">
				<i class="bi bi-shield-check text-primary"></i>
				Policies &amp; Info
			</h2>
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
				@php
				$policyModules = config('modules.policy', []);
				@endphp

				@foreach($policyModules as $module)
				<a href="{{ url($module['url']) }}" class="block p-4 bg-muted/50 hover:bg-muted rounded-lg border border-border hover:border-primary transition-all group">
					<div class="flex items-start gap-3">
						<div class="p-2 bg-primary/10 rounded-lg group-hover:bg-primary/20 transition-colors">
							<i class="{{ $module['icon'] }} text-primary text-xl"></i>
						</div>
						<div class="flex-1">
							<h3 class="font-semibold text-foreground group-hover:text-primary transition-colors">{{ $module['name'] }}</h3>
							<p class="text-sm text-muted-foreground mt-1">{{ $module['description'] }}</p>
						</div>
					</div>
				</a>
				@endforeach
			</div>
		</div>
	</div>

	<!-- Admin Modules -->
	@can('admin')
	<div class="card-tw">
		<div class="p-6">
			<h2 class="text-2xl font-bold text-foreground mb-4 flex items-center gap-2">
				<i class="bi bi-shield-lock text-primary"></i>
				Admin Modules
			</h2>
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
				@php
				$adminModules = config('modules.admin', []);
				sort($adminModules);
				@endphp

				@foreach($adminModules as $module)
				<a href="{{ url($module['url']) }}" class="block p-4 bg-muted/50 hover:bg-muted rounded-lg border border-border hover:border-primary transition-all group">
					<div class="flex items-start gap-3">
						<div class="p-2 bg-primary/10 rounded-lg group-hover:bg-primary/20 transition-colors">
							<i class="{{ $module['icon'] }} text-primary text-xl"></i>
						</div>
						<div class="flex-1">
							<h3 class="font-semibold text-foreground group-hover:text-primary transition-colors">{{ $module['name'] }}</h3>
							<p class="text-sm text-muted-foreground mt-1">{{ $module['description'] }}</p>
						</div>
					</div>
				</a>
				@endforeach
			</div>
		</div>
	</div>
	@endcan
</div>

@stop
