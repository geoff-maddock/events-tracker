@extends('layouts.app-tw')

@section('title', 'Edit Event Review')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Edit Review</h1>
		<p class="text-muted-foreground">
			Event: <a href="{{ route('events.show', ['event' => $review->event->id]) }}" class="text-primary hover:text-primary/90">{{ $review->event->name }}</a>
		</p>
	</div>

	<!-- Action Menu -->
	<div class="flex flex-wrap gap-3 mb-6">
		<a href="{{ route('reviews.index') }}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
			<i class="bi bi-list mr-2"></i>
			Reviews List
		</a>
		<a href="{{ route('events.show', ['event' => $review->event->id]) }}" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
			<i class="bi bi-eye mr-2"></i>
			View Event
		</a>
	</div>

	<!-- Form Card -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<form action="{{ route('events.reviews.update', [$review->event->id, $review->id]) }}" method="POST">
				@csrf
				@method('PATCH')

				@include('reviews.form-tw', ['action' => 'update'])
			</form>
		</div>
	</div>

	<!-- Delete Section -->
	<div class="card-tw border-destructive/20">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-destructive mb-2">Danger Zone</h2>
			<p class="text-sm text-muted-foreground mb-4">Once you delete a review, there is no going back. Please be certain.</p>

			<form action="{{ route('events.reviews.destroy', [$review->event->id, $review->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
				@csrf
				@method('DELETE')
				<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors">
					<i class="bi bi-trash mr-2"></i>
					Delete Review
				</button>
			</form>
		</div>
	</div>
</div>

@stop
