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
				$publicModules = [
					['name' => 'Activity', 'url' => '/activity', 'icon' => 'bi-activity', 'description' => 'View recent site activity'],
					['name' => 'Calendar', 'url' => '/calendar', 'icon' => 'bi-calendar3', 'description' => 'Browse events by calendar'],
					['name' => 'Entities', 'url' => '/entities', 'icon' => 'bi-people', 'description' => 'Artists, venues, promoters'],
					['name' => 'Events', 'url' => '/events', 'icon' => 'bi-calendar-event', 'description' => 'Concerts and club nights'],
					['name' => 'Photos', 'url' => '/photos', 'icon' => 'bi-images', 'description' => 'Browse event photos'],
					['name' => 'Popular', 'url' => '/popular', 'icon' => 'bi-graph-up-arrow', 'description' => 'Popular events, entities, and tags'],
					['name' => 'Posts', 'url' => '/posts', 'icon' => 'bi-file-text', 'description' => 'Community posts'],
					['name' => 'Reviews', 'url' => '/reviews', 'icon' => 'bi-star', 'description' => 'Event reviews'],
					['name' => 'Search', 'url' => '/search', 'icon' => 'bi-search', 'description' => 'Search events, entities, series, and more'],
					['name' => 'Series', 'url' => '/series', 'icon' => 'bi-collection', 'description' => 'Recurring event series'],
					['name' => 'Tags', 'url' => '/tags', 'icon' => 'bi-tags', 'description' => 'Browse by tags'],
					['name' => 'Threads', 'url' => '/threads', 'icon' => 'bi-chat-dots', 'description' => 'Forum discussions'],
					['name' => 'Users', 'url' => '/users', 'icon' => 'bi-person', 'description' => 'Community members'],
				];
				sort($publicModules);
				@endphp

				@foreach($publicModules as $module)
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

				@auth
				@php $notificationsUnread = Auth::user()->unreadNotifications()->count(); @endphp
				<a href="{{ route('job-status.index') }}" class="block p-4 bg-muted/50 hover:bg-muted rounded-lg border border-border hover:border-primary transition-all group">
					<div class="flex items-start gap-3">
						<div class="p-2 bg-primary/10 rounded-lg group-hover:bg-primary/20 transition-colors">
							<i class="bi bi-bell text-primary text-xl"></i>
						</div>
						<div class="flex-1">
							<h3 class="font-semibold text-foreground group-hover:text-primary transition-colors">
								Notifications
								@if($notificationsUnread > 0)
								<span class="ml-2 inline-flex items-center justify-center text-xs font-semibold rounded-full bg-red-600 text-white px-2 py-0.5">{{ $notificationsUnread }}</span>
								@endif
							</h3>
							<p class="text-sm text-muted-foreground mt-1">Background jobs and notifications</p>
						</div>
					</div>
				</a>
				@endauth
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
				$policyModules = [
					['name' => 'Privacy Policy', 'url' => '/privacy', 'icon' => 'bi-shield-check', 'description' => 'How we handle your data'],
					['name' => 'Terms of Service', 'url' => '/tos', 'icon' => 'bi-file-earmark-text', 'description' => 'Terms and conditions of use'],
					['name' => 'About', 'url' => '/about', 'icon' => 'bi-info-circle', 'description' => 'About this site'],
					['name' => 'Help', 'url' => '/help', 'icon' => 'bi-question-circle', 'description' => 'How to use the site'],
				];
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
				$adminModules = [
					['name' => 'Activity Graph', 'url' => '/activity/graph', 'icon' => 'bi-activity', 'description' => 'Visualize and export activity trends'],
					['name' => 'Blogs', 'url' => '/blogs', 'icon' => 'bi-journal-text', 'description' => 'Manage blog posts'],
					['name' => 'Categories', 'url' => '/categories', 'icon' => 'bi-folder', 'description' => 'Manage forum categories'],
					['name' => 'Entity Types', 'url' => '/entity-types', 'icon' => 'bi-diagram-3', 'description' => 'Manage entity types'],
					['name' => 'Forums', 'url' => '/forums', 'icon' => 'bi-chat-square-text', 'description' => 'Manage forum sections'],
					['name' => 'Groups', 'url' => '/groups', 'icon' => 'bi-people-fill', 'description' => 'Manage user groups'],
					['name' => 'Menus', 'url' => '/menus', 'icon' => 'bi-menu-button-wide', 'description' => 'Manage navigation menus'],
					['name' => 'Permissions', 'url' => '/permissions', 'icon' => 'bi-key', 'description' => 'Manage permissions'],
					['name' => 'Roles', 'url' => '/roles', 'icon' => 'bi-person-badge', 'description' => 'Manage entity roles'],
				];
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
