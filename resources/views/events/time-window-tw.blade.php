@extends('layouts.app-tw')

@section('title', $window->title())

@php
    $desc = $window->metaDescription($stats);
@endphp

@section('description', $desc)
@section('og-description', $desc)

@if ($events->isNotEmpty())
@section('page.json')
@include('events.index-json-ld', [
    'pageUrl' => url($window->path()),
    'pageName' => $window->h1(),
    'pageDesc' => $desc,
])
@endsection
@endif

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">{{ $window->h1() }}</h1>
	<p class="text-muted-foreground">{{ $desc }}</p>
</div>

<!-- Other windows -->
<div class="mb-6 flex flex-wrap gap-2">
	@foreach (\App\Enums\EventTimeWindow::cases() as $otherWindow)
	<a href="{{ url($otherWindow->path()) }}"
		class="inline-flex items-center px-3 py-1 rounded-full text-sm border transition-colors {{ $otherWindow === $window ? 'bg-primary text-primary-foreground border-primary' : 'border-border text-muted-foreground hover:bg-accent' }}">
		{{ $otherWindow->label() }}
	</a>
	@endforeach
</div>

@if ($events->isNotEmpty())
<div class="mb-4">
	{!! $events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
	@foreach ($events as $event)
	@include('events.card-tw', ['event' => $event])
	@endforeach
</div>

<div class="mt-4">
	{!! $events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
</div>
@else
<div class="text-center py-12">
	<i class="bi bi-calendar-x text-6xl text-muted-foreground/60 mb-4"></i>
	<p class="text-muted-foreground">No events found for this window yet.</p>
	<a href="{{ url('/events') }}" class="mt-4 inline-flex items-center text-primary hover:text-primary/90">
		<i class="bi bi-arrow-left mr-2"></i>
		View all events
	</a>
</div>
@endif

@endsection
