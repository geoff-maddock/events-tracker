@extends('layouts.app-tw')

@section('title', 'Edit Forum')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Edit Forum</h1>
		<p class="text-muted-foreground">{{ $forum->name }}</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<form action="{{ route('forums.update', $forum->id) }}" method="POST">
				@csrf
				@method('PATCH')

				@include('forums.form-tw', ['action' => 'update'])
			</form>
		</div>
	</div>

	<!-- Delete Section -->
	<div class="card-tw border-destructive/20">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-destructive mb-2">Danger Zone</h2>
			<p class="text-sm text-muted-foreground mb-4">Once you delete a forum, there is no going back. Please be certain.</p>

			<form action="{{ route('forums.destroy', $forum->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this forum? This action cannot be undone.');">
				@csrf
				@method('DELETE')
				<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors">
					<i class="bi bi-trash mr-2"></i>
					Delete Forum
				</button>
			</form>
		</div>
	</div>
</div>

@stop
