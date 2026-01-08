@extends('app')

@section('title', 'About')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-8">
		<h1 class="text-4xl font-bold text-primary mb-4">About {{ config('app.app_name')}}</h1>
	</div>

	<!-- Content Card -->
	<div class="card-tw">
		<div class="p-6 space-y-6">
			<!-- Mission Statement -->
			<div>
				<h2 class="text-2xl font-semibold text-foreground mb-3">Our Mission</h2>
				<p class="text-muted-foreground leading-relaxed">
					{{ config('app.app_name') }} is a community-driven platform for discovering and sharing information about the local music and arts scene. Based in Pittsburgh, PA, we provide a comprehensive calendar of events, club nights, concert series, and more.
				</p>
			</div>

			<!-- What We Offer -->
			<div>
				<h2 class="text-2xl font-semibold text-foreground mb-3">What We Offer</h2>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div class="flex items-start gap-3">
						<div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
							<i class="bi bi-calendar-event text-primary text-xl"></i>
						</div>
						<div>
							<h3 class="text-foreground font-semibold mb-1">Event Listings</h3>
							<p class="text-sm text-muted-foreground">Comprehensive calendar of upcoming shows, concerts, and club nights.</p>
						</div>
					</div>

					<div class="flex items-start gap-3">
						<div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
							<i class="bi bi-collection text-primary text-xl"></i>
						</div>
						<div>
							<h3 class="text-foreground font-semibold mb-1">Event Series</h3>
							<p class="text-sm text-muted-foreground">Recurring events and weekly/monthly series information.</p>
						</div>
					</div>

					<div class="flex items-start gap-3">
						<div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
							<i class="bi bi-people text-primary text-xl"></i>
						</div>
						<div>
							<h3 class="text-foreground font-semibold mb-1">Entity Directory</h3>
							<p class="text-sm text-muted-foreground">Database of artists, promoters, venues, DJs, and producers.</p>
						</div>
					</div>

					<div class="flex items-start gap-3">
						<div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
							<i class="bi bi-chat-dots text-primary text-xl"></i>
						</div>
						<div>
							<h3 class="text-foreground font-semibold mb-1">Community Forum</h3>
							<p class="text-sm text-muted-foreground">Discussion threads and community engagement.</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Get Involved -->
			<div>
				<h2 class="text-2xl font-semibold text-foreground mb-3">Get Involved</h2>
				<p class="text-muted-foreground mb-4">
					{{ config('app.app_name') }} is built by and for the community. Anyone can contribute by adding events, creating profiles for venues and artists, or participating in discussions.
				</p>
				<div class="flex flex-wrap gap-3">
					@if (Auth::guest())
					<a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-colors">
						<i class="bi bi-person-plus mr-2"></i>
						Register an Account
					</a>
					@endif
					<a href="{{ route('events.create') }}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
						<i class="bi bi-plus-lg mr-2"></i>
						Add an Event
					</a>
					<a href="{{ route('entities.create') }}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
						<i class="bi bi-plus-lg mr-2"></i>
						Add an Entity
					</a>
				</div>
			</div>

			<!-- Contact/Links -->
			<div>
				<h2 class="text-2xl font-semibold text-foreground mb-3">Stay Connected</h2>
				<div class="flex flex-wrap gap-4">
					<a href="{{ route('help') }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
						<i class="bi bi-question-circle"></i>
						<span>Help & FAQ</span>
					</a>
					<a href="{{ route('privacy') }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
						<i class="bi bi-shield-check"></i>
						<span>Privacy Policy</span>
					</a>
					@isset($hasForum)
					<a href="{{ url('/threads') }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
						<i class="bi bi-chat-dots"></i>
						<span>Forum</span>
					</a>
					@endisset
				</div>
			</div>
		</div>
	</div>
</div>

@stop

@section('footer')
@stop
