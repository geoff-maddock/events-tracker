@extends('layouts.app-tw')

@section('title', $window->title() . ($activeTag ? ' — ' . $activeTag->name : ''))

@php
    $desc = $window->metaDescription($stats);
    $tagLinks = $stats['tagLinks'] ?? [];
    $visibleTagCount = \App\Services\EventTimeWindowStats::TAG_LINKS_VISIBLE;
@endphp

@section('description', $desc)
@section('og-description', $desc)

@if ($events->isNotEmpty() && ! $activeTag)
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
	<h1 class="text-3xl font-bold text-primary mb-2">{{ $window->h1() }}{{ $activeTag ? ' — ' . $activeTag->name : '' }}</h1>
	<p class="text-muted-foreground">{{ $desc }}</p>
</div>

<!-- Other windows -->
<div class="mb-4 flex flex-wrap gap-2">
	@foreach (\App\Enums\EventTimeWindow::cases() as $otherWindow)
	<a href="{{ url($otherWindow->path()) }}"
		class="inline-flex items-center px-3 py-1 rounded-full text-sm border transition-colors {{ $otherWindow === $window ? 'bg-primary text-primary-foreground border-primary' : 'border-border text-muted-foreground hover:bg-accent' }}">
		{{ $otherWindow->label() }}
	</a>
	@endforeach
</div>

<!-- Tag drill-down pills -->
@if (! empty($tagLinks))
<div class="mb-6 flex flex-wrap items-center gap-2" x-data="{ expanded: false }">
	@foreach ($tagLinks as $index => $tagLink)
	@php
		$isActive = $activeTag && $activeTag->slug === $tagLink['slug'];
		$isFolded = $index >= $visibleTagCount && ! $isActive;
	@endphp
	<a href="{{ $isActive ? url($window->path()) : url($window->path()) . '?tag=' . urlencode($tagLink['slug']) }}"
		@if ($isFolded) x-show="expanded" style="display: none;" @endif
		title="{{ $isActive ? 'Clear tag filter' : 'Only ' . $tagLink['name'] . ' events' }}"
		class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs border transition-colors {{ $isActive ? 'bg-primary text-primary-foreground border-primary' : 'border-border text-muted-foreground hover:bg-accent' }}">
		{{ $tagLink['name'] }}
		<span class="{{ $isActive ? 'text-primary-foreground/80' : 'text-muted-foreground/70' }}">{{ $tagLink['count'] }}</span>
		@if ($isActive)
		<i class="bi bi-x ml-0.5" aria-hidden="true"></i>
		@endif
	</a>
	@endforeach
	@if (count($tagLinks) > $visibleTagCount)
	<button type="button" x-show="! expanded" @click="expanded = true"
		class="inline-flex items-center px-3 py-1 rounded-full text-xs border border-border text-muted-foreground hover:bg-accent transition-colors"
		aria-label="Show all tags">
		&hellip;
	</button>
	@endif
</div>
@endif

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
	@if ($activeTag)
	<p class="text-muted-foreground">No {{ $activeTag->name }} events found for this window yet.</p>
	<a href="{{ url($window->path()) }}" class="mt-4 inline-flex items-center text-primary hover:text-primary/90">
		<i class="bi bi-arrow-left mr-2"></i>
		View all {{ strtolower($window->label()) }} events
	</a>
	@else
	<p class="text-muted-foreground">No events found for this window yet.</p>
	<a href="{{ url('/events') }}" class="mt-4 inline-flex items-center text-primary hover:text-primary/90">
		<i class="bi bi-arrow-left mr-2"></i>
		View all events
	</a>
	@endif
</div>
@endif

@endsection
