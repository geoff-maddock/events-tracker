@extends('layouts.app-tw')

@section('title', 'Edit Discord Target')

@section('content')

<div class="max-w-4xl mx-auto">
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Edit {{ $target->name }}</h1>
		<p class="text-muted-foreground">Change what this channel receives and when.</p>
	</div>

	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('discord-targets.update', ['discordTarget' => $target->id]) }}" method="POST">
				@csrf
				@method('PATCH')

				@include('discord-targets.form-tw', ['action' => 'update'])
			</form>
		</div>
	</div>

	{{-- Danger Zone --}}
	<div class="card-tw mt-6 border-l-4 border-destructive">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-foreground mb-2">Danger Zone</h2>
			<p class="text-sm text-muted-foreground mb-4">
				Deleting this target removes its filters and stops all posting to the channel.
				The post history is removed with it.
			</p>
			<form action="{{ route('discord-targets.destroy', ['discordTarget' => $target->id]) }}" method="POST">
				@csrf
				@method('DELETE')
				<button type="submit" class="delete px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors"
					data-confirm="Delete the Discord target &quot;{{ $target->name }}&quot;?">
					<i class="bi bi-trash mr-2"></i>Delete Target
				</button>
			</form>
		</div>
	</div>

	<div class="mt-4">
		<a href="{{ route('discord-targets.show', ['discordTarget' => $target->id]) }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
			<i class="bi bi-arrow-left"></i>
			<span>Back to target</span>
		</a>
	</div>
</div>

@stop
