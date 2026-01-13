@extends('layouts.app-tw')

@section('title', 'Add Event Review')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Add a Review</h1>
		<p class="text-muted-foreground">
			Event: <a href="{{ route('events.show', ['event' => $event->id]) }}" class="text-primary hover:text-primary/90">{{ $event->name }}</a>
		</p>
	</div>

	<!-- Action Menu -->
	<div class="flex flex-wrap gap-3 mb-6">
		<a href="{{ route('events.show', ['event' => $event->id]) }}" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
			<i class="bi bi-eye mr-2"></i>
			View Event
		</a>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('events.reviews.store', $event->id) }}" method="POST">
				@csrf

				@include('reviews.form-tw')
			</form>
		</div>
	</div>
</div>

@stop
