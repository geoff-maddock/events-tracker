@extends('layouts.app-tw')

@section('title','Event Series - Create Occurrence')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
	<!-- Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-foreground mb-2">Create Event Occurrence</h1>
		<p class="text-sm text-muted-foreground">Series: {{ $series->name}}</p>
		<p class="text-sm text-muted-foreground">Event: {{ $event->name }}</p>
	</div>

	<!-- Form Card -->
	<div class="bg-card rounded-lg border border-border shadow-sm p-6">
		<form method="POST" action="{{ route('events.store') }}" class="space-y-6">
			@csrf

			@include('events.form', ['action' => 'createOccurrence'])

			<!-- Submit Buttons -->
			<div class="flex items-center gap-3 pt-6 border-t border-border">
				<x-ui.button type="submit" variant="primary">
					<i class="bi bi-plus-circle mr-2"></i>
					Create Occurrence
				</x-ui.button>

				<x-ui.button variant="ghost" href="{{ route('series.index') }}">
					Cancel
				</x-ui.button>
			</div>
		</form>
	</div>

	<!-- Back Button -->
	<div class="mt-6">
		<x-ui.button variant="ghost" href="{{ route('series.index') }}">
			<i class="bi bi-arrow-left mr-2"></i>
			Return to Series
		</x-ui.button>
	</div>
</div>

@stop
