@extends('layouts.app-tw')

@section('title','Event - Create Series')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
	<!-- Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-foreground mb-2">Create Series from Event</h1>
		<p class="text-sm text-muted-foreground">{{ $event->name}}</p>
	</div>

	<!-- Form Card -->
	<div class="bg-card rounded-lg border border-border shadow-sm p-6">
		<form method="POST" action="{{ route('series.store') }}" class="space-y-6">
			@csrf

			@include('series.form', ['action' => 'createSeries', 'eventLinkId' => $event->id])

			<!-- Submit Buttons -->
			<div class="flex items-center gap-3 pt-6 border-t border-border">
				<x-ui.button type="submit" variant="primary">
					<i class="bi bi-plus-circle mr-2"></i>
					Create Series
				</x-ui.button>

				<x-ui.button variant="ghost" href="{{ route('events.index') }}">
					Cancel
				</x-ui.button>
			</div>
		</form>
	</div>

	<!-- Back Button -->
	<div class="mt-6">
		<x-ui.button variant="ghost" href="{{ route('events.index') }}">
			<i class="bi bi-arrow-left mr-2"></i>
			Return to Events
		</x-ui.button>
	</div>
</div>

@stop
