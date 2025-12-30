@extends('app')

@section('title'){{ config('app.tagline')}}@endsection

@section('content')

<!-- Hero Section -->
<div class="relative bg-gradient-to-br from-primary/20 to-accent/20 rounded-lg p-8 mb-6 border border-dark-border">
	<div class="flex items-start justify-between mb-4">
		<h1 class="text-4xl font-bold text-white">{{ config('app.tagline')}}</h1>
		<button id="home-toggle" class="text-gray-400 hover:text-white">
			<i class="bi bi-chevron-up" id="home-chevron"></i>
		</button>
	</div>
	
	<div id="home-content" class="space-y-4">
		<p class="text-lg text-gray-300">
			{{ config('app.app_name') }} is a calendar and guide to events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities that are part of the Pittsburgh scene.
		</p>
		
		<!-- Action Buttons -->
		<div class="flex flex-wrap gap-3">
			<a href="{!! URL::route('events.index') !!}" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
				<i class="bi bi-calendar-event mr-2"></i>
				Show All Events
			</a>
			<a href="{!! URL::route('events.future') !!}" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
				<i class="bi bi-calendar3 mr-2"></i>
				Show Future Events
			</a>
			<a href="{!! URL::route('series.index') !!}" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
				<i class="bi bi-collection mr-2"></i>
				Show Event Series
			</a>
			<a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
				<i class="bi bi-plus-lg mr-2"></i>
				Add an Event
			</a>
			<a href="{!! URL::route('series.create') !!}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
				<i class="bi bi-plus-lg mr-2"></i>
				Add an Event Series
			</a>
			<a href="{!! URL::route('entities.create') !!}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
				<i class="bi bi-plus-lg mr-2"></i>
				Add an Entity
			</a>
			@if (Auth::guest())
			<a href="{!! URL::route('register') !!}" class="inline-flex items-center px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent-hover transition-colors">
				<i class="bi bi-person-plus mr-2"></i>
				Register Account
			</a>
			@endif
		</div>
	</div>
</div>

<!-- Upcoming Events Section -->
<section id="upcoming-events">
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
	} else {
		chevron.classList.remove('bi-chevron-down');
		chevron.classList.add('bi-chevron-up');
	}
});

// init app module on document load
$(function()
{
    Home.init();
});
</script>
@stop
