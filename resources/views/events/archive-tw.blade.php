@extends('layouts.app-tw')

@section('title', 'Event Archive')
@section('description', 'Browse past and upcoming events month by month.')

@section('content')

<div class="max-w-5xl mx-auto">
	<!-- Header -->
	<div class="mb-8">
		<h1 class="text-3xl font-bold text-foreground mb-2">Event Archive</h1>
		<p class="text-muted-foreground">Browse events month by month.</p>
	</div>

	@forelse ($years as $year => $months)
	<div class="mb-8">
		<h2 class="text-2xl font-semibold text-foreground mb-4">{{ $year }}</h2>
		<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
			@foreach ($months as $month)
			<a href="{{ url('/events/by-date/'.$month->year.'/'.$month->month) }}"
			   class="flex flex-col items-center p-4 bg-card border border-border rounded-lg hover:bg-accent hover:border-primary transition-colors">
				<span class="font-medium text-foreground">{{ \Carbon\Carbon::create($month->year, $month->month, 1)->format('F') }}</span>
				<span class="text-sm text-muted-foreground">{{ $month->event_count }} {{ Str::plural('event', $month->event_count) }}</span>
			</a>
			@endforeach
		</div>
	</div>
	@empty
	<div class="text-center py-12 bg-card rounded-lg border border-border">
		<i class="bi bi-calendar-x text-4xl text-muted-foreground/50 mb-3 block"></i>
		<p class="text-muted-foreground">No events found.</p>
	</div>
	@endforelse
</div>

@stop
