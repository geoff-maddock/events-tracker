@extends('layouts.app-tw')

@section('title'){{ config('app.tagline')}}@endsection

@section('content')

<!-- Hero Section -->
<div class="relative overflow-hidden bg-gradient-to-br from-primary/10 via-background to-accent/5 rounded-xl border border-border shadow-sm mb-8">
	<div class="relative p-6 md:p-8 lg:p-10">
		<!-- Header with Toggle -->
		<div class="flex items-start justify-between gap-4 mb-6">
			<div class="flex-1">
				<h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-3">
					{{ config('app.tagline')}}
				</h1>
				<p class="text-base md:text-lg text-muted-foreground max-w-3xl">
					{{ config('app.app_name') }} is your calendar and guide to the vibrant Pittsburgh scene — events, weekly series, venues, artists, promoters, and everything in between.
				</p>
			</div>
			<button
				id="home-toggle"
				class="flex-shrink-0 p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
				aria-label="Toggle welcome section">
				<i class="bi bi-chevron-up text-xl" id="home-chevron"></i>
			</button>
		</div>

		<!-- Collapsible Content -->
		<div id="home-content" class="space-y-6">
			<!-- Features Grid -->
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
				<div class="flex items-start gap-3 p-4 rounded-lg bg-card/50 border border-border/50">
					<div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
						<i class="bi bi-calendar-event text-primary text-xl"></i>
					</div>
					<div>
						<h3 class="font-semibold text-foreground mb-1">Discover Events</h3>
						<p class="text-sm text-muted-foreground">Find concerts, shows, and happenings across Pittsburgh</p>
					</div>
				</div>

				<div class="flex items-start gap-3 p-4 rounded-lg bg-card/50 border border-border/50">
					<div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
						<i class="bi bi-people text-primary text-xl"></i>
					</div>
					<div>
						<h3 class="font-semibold text-foreground mb-1">Connect</h3>
						<p class="text-sm text-muted-foreground">Follow artists, venues, and promoters you love</p>
					</div>
				</div>

				<div class="flex items-start gap-3 p-4 rounded-lg bg-card/50 border border-border/50">
					<div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
						<i class="bi bi-plus-circle text-primary text-xl"></i>
					</div>
					<div>
						<h3 class="font-semibold text-foreground mb-1">Contribute</h3>
						<p class="text-sm text-muted-foreground">Add events and help grow the community</p>
					</div>
				</div>
			</div>

			<!-- Quick Actions -->
			<div>
				<h2 class="text-lg font-semibold text-foreground mb-3">Quick Actions</h2>
				<div class="flex flex-wrap gap-3">
					<!-- Browse Actions -->
					<a href="{!! URL::route('events.index') !!}"
						class="inline-flex items-center gap-2 px-4 py-2.5 bg-card border border-border text-foreground rounded-lg hover:bg-accent hover:border-primary/30 transition-all group">
						<i class="bi bi-calendar-event text-muted-foreground group-hover:text-primary transition-colors"></i>
						<span class="font-medium">All Events</span>
					</a>

					<a href="{!! URL::route('series.index') !!}"
						class="inline-flex items-center gap-2 px-4 py-2.5 bg-card border border-border text-foreground rounded-lg hover:bg-accent hover:border-primary/30 transition-all group">
						<i class="bi bi-collection text-muted-foreground group-hover:text-primary transition-colors"></i>
						<span class="font-medium">Event Series</span>
					</a>

					<a href="{!! URL::route('entities.index') !!}"
						class="inline-flex items-center gap-2 px-4 py-2.5 bg-card border border-border text-foreground rounded-lg hover:bg-accent hover:border-primary/30 transition-all group">
						<i class="bi bi-people text-muted-foreground group-hover:text-primary transition-colors"></i>
						<span class="font-medium">Entities</span>
					</a>

					<!-- Divider -->
					<div class="w-px bg-border self-stretch hidden sm:block"></div>

					<!-- Create Actions -->
					<a href="{!! URL::route('events.create') !!}"
						class="inline-flex items-center gap-2 px-4 py-2.5 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-all shadow-sm">
						<i class="bi bi-plus-lg"></i>
						<span class="font-medium">Add Event</span>
					</a>

					<a href="{!! URL::route('series.create') !!}"
						class="inline-flex items-center gap-2 px-4 py-2.5 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-all shadow-sm">
						<i class="bi bi-plus-lg"></i>
						<span class="font-medium">Add Series</span>
					</a>

					<a href="{!! URL::route('entities.create') !!}"
						class="inline-flex items-center gap-2 px-4 py-2.5 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-all shadow-sm">
						<i class="bi bi-plus-lg"></i>
						<span class="font-medium">Add Entity</span>
					</a>

					@if (Auth::guest())
					<a href="{!! URL::route('register') !!}"
						class="inline-flex items-center gap-2 px-4 py-2.5 bg-accent text-foreground rounded-lg hover:bg-accent/80 transition-all border border-border">
						<i class="bi bi-person-plus"></i>
						<span class="font-medium">Register</span>
					</a>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Upcoming Events Section -->
<section id="upcoming-events" class="space-y-6">
	<div class="flex items-center justify-between">
		<h2 class="text-2xl md:text-3xl font-bold text-foreground">Upcoming Events</h2>
		<a href="{!! URL::route('events.index') !!}"
			class="text-sm font-medium text-primary hover:text-primary/90 transition-colors inline-flex items-center gap-1">
			View All
			<i class="bi bi-arrow-right"></i>
		</a>
	</div>

	@include('events.4days-tw')
</section>

@stop

@section('scripts.footer')
<script type="text/javascript">
// Toggle home content
document.getElementById('home-toggle')?.addEventListener('click', function() {
	const content = document.getElementById('home-content');
	const chevron = document.getElementById('home-chevron');

	content.classList.toggle('hidden');

	if (content.classList.contains('hidden')) {
		chevron.classList.remove('bi-chevron-up');
		chevron.classList.add('bi-chevron-down');
		this.setAttribute('aria-expanded', 'false');
	} else {
		chevron.classList.remove('bi-chevron-down');
		chevron.classList.add('bi-chevron-up');
		this.setAttribute('aria-expanded', 'true');
	}
});

// init app module on document load
$(function() {
	Home.init();
});
</script>
@stop
