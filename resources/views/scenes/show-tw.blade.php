@extends('layouts.app-tw')

@section('title', $scene['title'])

@php
    $primaryTag = $scene['tags'][0];
    $desc = rtrim($scene['description'], '.') . '. ' . $stats['events'] . ' upcoming ' . Str::plural('event', $stats['events']) . ' across ' . $stats['venues'] . ' ' . Str::plural('venue', $stats['venues']) . '.';
@endphp

@section('description', $desc)
@section('og-description', $desc)

@if ($events->isNotEmpty())
@section('page.json')
@include('events.index-json-ld', [
    'pageUrl' => url('/scenes/'.$slug),
    'pageName' => $scene['name'],
    'pageDesc' => $desc,
])
@endsection
@endif

@section('content')

<div class="mx-auto px-6 py-8 max-w-[2400px]">
	<div class="space-y-8">
		<!-- Back Button -->
		<div class="flex items-center gap-4">
			<a href="{{ url('/scenes') }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm border border-border rounded-lg hover:bg-accent transition-colors">
				<i class="bi bi-arrow-left"></i>
				Back to Scenes
			</a>
		</div>

		<!-- Page Header -->
		<div>
			<h1 class="text-4xl font-bold text-foreground mb-2">{{ $scene['name'] }}</h1>
			<p class="text-muted-foreground">
				{{ $stats['events'] }} upcoming {{ Str::plural('event', $stats['events']) }} &middot;
				{{ $stats['venues'] }} {{ Str::plural('venue', $stats['venues']) }}
			</p>
		</div>

		<!-- Sibling Scenes -->
		<div class="flex flex-wrap gap-2">
			@foreach (config('scenes') as $siblingSlug => $sibling)
			<a href="{{ url('/scenes/'.$siblingSlug) }}"
				class="inline-flex items-center px-3 py-1 rounded-full text-sm border transition-colors {{ $siblingSlug === $slug ? 'bg-primary text-primary-foreground border-primary' : 'border-border text-muted-foreground hover:bg-accent' }}">
				{{ $sibling['name'] }}
			</a>
			@endforeach
		</div>

		<!-- Editorial Copy -->
		<div class="max-w-3xl">
			@include('scenes.copy.'.$slug)
		</div>

		<!-- Upcoming Events -->
		<div>
			<div class="flex items-baseline gap-3 mb-4">
				<h2 class="text-2xl font-semibold text-foreground">Upcoming Events</h2>
				<a href="{{ url('/events?filters[tag]='.$primaryTag) }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
					View all
				</a>
			</div>
			@if ($events->isNotEmpty())
			<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
				@foreach ($events as $event)
				@include('events.card-tw', ['event' => $event, 'series' => null, 'entity' => null])
				@endforeach
			</div>
			@else
			<div class="text-center py-12 bg-card rounded-lg border border-border">
				<i class="bi bi-calendar-x text-4xl text-muted-foreground/50 mb-3 block"></i>
				<p class="text-muted-foreground">No upcoming {{ $scene['name'] }} events posted yet.</p>
			</div>
			@endif
		</div>

		<!-- Key Venues & Artists -->
		@if ($entities->isNotEmpty())
		<div>
			<div class="flex items-baseline gap-3 mb-4">
				<h2 class="text-2xl font-semibold text-foreground">Key Venues &amp; Artists</h2>
				<a href="{{ url('/entities?filters[tag]='.$primaryTag) }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
					View all
				</a>
			</div>
			<div class="flex flex-wrap gap-2">
				@foreach ($entities as $entity)
				<a href="{{ route('entities.show', $entity->slug) }}"
					class="inline-flex items-center gap-2 pl-1 pr-3 py-1 rounded-full border border-border bg-card hover:border-primary hover:bg-accent transition-colors"
					title="{{ $entity->name }}">
					@if ($primary = $entity->getPrimaryPhoto())
					<img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="" class="w-6 h-6 rounded-full object-cover">
					@else
					<span class="w-6 h-6 rounded-full bg-muted flex items-center justify-center">
						<i class="bi bi-building text-xs text-muted-foreground"></i>
					</span>
					@endif
					<span class="text-sm font-medium text-foreground">{{ $entity->name }}</span>
					@if ($role = $entity->roles->first())
					<span class="text-xs text-muted-foreground">{{ $role->name }}</span>
					@endif
				</a>
				@endforeach
			</div>
		</div>
		@endif

		<!-- Active Series -->
		@if ($series->isNotEmpty())
		<div>
			<div class="flex items-baseline gap-3 mb-4">
				<h2 class="text-2xl font-semibold text-foreground">Active Series</h2>
				<a href="{{ url('/series?filters[tag]='.$primaryTag) }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
					View all
				</a>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
				@foreach ($series as $s)
				@include('series.card-tw', ['series' => $s, 'event' => null, 'entity' => null])
				@endforeach
			</div>
		</div>
		@endif
	</div>
</div>

@stop
