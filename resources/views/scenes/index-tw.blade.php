@extends('layouts.app-tw')

@section('title', 'Pittsburgh Music Scenes')

@php
    $desc = 'Curated genre hubs for Pittsburgh — '.collect(config('scenes'))->pluck('name')->map(fn ($name) => Str::lower($name))->join(', ', ' and ').' — with upcoming events, key venues and artists, and active series.';
@endphp

@section('description', $desc)
@section('og-description', $desc)

@section('content')

<div class="mx-auto px-6 py-8 max-w-[2400px]">
	<div class="space-y-6">
		<!-- Page Header -->
		<div class="mb-2">
			<h1 class="text-4xl font-bold text-foreground">Scenes</h1>
			<p class="mt-2 text-muted-foreground max-w-3xl">{{ $desc }}</p>
		</div>

		<!-- Scene Cards -->
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
			@foreach ($scenes as $scene)
			<a href="{{ url('/scenes/'.$scene['slug']) }}"
				class="block rounded-lg border border-border bg-card overflow-hidden hover:border-primary hover:shadow-md transition-all">
				<div class="w-full h-36 bg-muted flex items-center justify-center">
					<i class="bi {{ $scene['icon'] ?? 'bi-music-note-beamed' }} text-4xl text-muted-foreground/60"></i>
				</div>
				<div class="p-6">
					<h2 class="text-xl font-semibold text-foreground mb-2">{{ $scene['name'] }}</h2>
					<p class="text-sm text-muted-foreground mb-4 line-clamp-2">{{ $scene['description'] }}</p>
					<div class="flex items-center gap-4 text-sm text-muted-foreground">
						<span class="inline-flex items-center gap-1">
							<i class="bi bi-calendar-event"></i>
							{{ $scene['stats']['events'] }} {{ Str::plural('event', $scene['stats']['events']) }}
						</span>
						<span class="inline-flex items-center gap-1">
							<i class="bi bi-geo-alt"></i>
							{{ $scene['stats']['venues'] }} {{ Str::plural('venue', $scene['stats']['venues']) }}
						</span>
					</div>
				</div>
			</a>
			@endforeach
		</div>
	</div>
</div>

@stop
