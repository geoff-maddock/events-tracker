@extends('layouts.app-tw')

@section('title', 'Add Discord Target')

@section('content')

<div class="max-w-4xl mx-auto">
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Add a Discord Target</h1>
		<p class="text-muted-foreground">Repost matching events into a Discord channel via an incoming webhook.</p>
	</div>

	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('discord-targets.store') }}" method="POST">
				@csrf

				@include('discord-targets.form-tw')
			</form>
		</div>
	</div>

	<div class="mt-4">
		<a href="{{ route('discord-targets.index') }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
			<i class="bi bi-arrow-left"></i>
			<span>Return to list</span>
		</a>
	</div>
</div>

@stop
