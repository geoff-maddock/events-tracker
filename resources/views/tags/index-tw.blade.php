@extends('layouts.app-tw')

@section('title', 'Tags')

@section('content')

<div class="flex flex-col gap-6">
	<!-- Page Header -->
	<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
		<h1 class="text-2xl font-bold text-foreground">
			Tags
			<span class="text-muted-foreground text-lg font-normal">(all tagged tags)</span>
		</h1>
		@if ($signedIn)
		<a href="{!! URL::route('tags.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
			<i class="bi bi-plus-lg mr-2"></i>
			Add Tag
		</a>
		@endif
	</div>

	<!-- Popular Tags Section -->
	@if (isset($latestTags) && count($latestTags) > 0)
	<div class="bg-card border border-border rounded-lg">
		<button id="popular-tags-toggle" class="w-full p-4 flex items-center justify-between text-left hover:bg-accent/50 transition-colors rounded-t-lg">
			<div class="flex items-center gap-2">
				<i class="bi bi-chevron-down transition-transform" id="popular-chevron"></i>
				<h2 class="text-lg font-semibold text-foreground">Popular Tags</h2>
			</div>
		</button>
		<div id="popular-tags-content" class="p-4 border-t border-border">
			<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
				@foreach($latestTags as $t)
					@php
						$event = $t->events()->visible($user ?? null)->latest('start_at')->first();
						$photo = $event ? $event->getPrimaryPhoto() : null;
					@endphp
					<div class="relative group">
						<a href="/tags/{{ $t->slug }}" class="block aspect-square bg-card border border-border rounded-lg overflow-hidden hover:border-primary transition-colors">
							@if($photo)
								<img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $t->name }}" class="w-full h-full object-cover">
							@else
								<div class="w-full h-full flex items-center justify-center bg-muted">
									<i class="bi bi-tag text-4xl text-muted-foreground/30"></i>
								</div>
							@endif
							<div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
								<div class="absolute bottom-0 left-0 right-0 p-2 text-white text-sm font-medium">
									{{ $t->name }}
								</div>
							</div>
						</a>
					</div>
				@endforeach
			</div>
		</div>
	</div>
	@endif

	<!-- Filters Section -->
	<div class="bg-card border border-border rounded-lg">
		<button id="filters-toggle" class="w-full p-4 flex items-center justify-between text-left hover:bg-accent/50 transition-colors rounded-t-lg">
			<div class="flex items-center gap-2">
				<i class="bi bi-chevron-{{ isset($hasFilter) && $hasFilter ? 'down' : 'right' }} transition-transform" id="filters-chevron"></i>
				<h2 class="text-lg font-semibold text-foreground">Filters</h2>
			</div>
			@if(isset($hasFilter) && $hasFilter)
			<a href="{{ url('/tags') }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
				Clear filters
			</a>
			@endif
		</button>
		<div id="filters-content" class="{{ isset($hasFilter) && $hasFilter ? '' : 'hidden' }} p-4 border-t border-border">
			<form method="GET" action="{{ url('/tags') }}" class="flex gap-2">
				<div class="flex-1">
					<input type="text"
						name="search"
						value="{{ $search ?? '' }}"
						placeholder="Search tags by name..."
						class="form-input-tw w-full">
				</div>
				<button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
					<i class="bi bi-search"></i>
				</button>
			</form>
		</div>
	</div>

	<!-- Pagination and Sorting Controls -->
	<div class="flex flex-wrap items-center justify-between gap-4 text-sm">
		<div class="flex items-center gap-2 text-muted-foreground">
			<span>{{ $tags->total() }} tags</span>
		</div>
		<div class="flex items-center gap-4">
			<div class="flex items-center gap-2">
				<span class="text-muted-foreground">20 per page</span>
			</div>
			<div class="flex items-center gap-2">
				<span class="text-muted-foreground">Sort by:</span>
				<span class="text-foreground">Name</span>
			</div>
			@if(isset($tags) && method_exists($tags, 'hasPages') && $tags->hasPages())
			<div class="flex items-center gap-2">
				@if($tags->hasMorePages())
				<a href="{{ $tags->nextPageUrl() }}" class="text-muted-foreground hover:text-foreground">Next &gt;</a>
				@endif
			</div>
			@endif
		</div>
	</div>

	<!-- Tags Grid -->
	<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
		@foreach ($tags as $tag)
			@php
				$event = $tag->events()->visible($user ?? null)->latest('start_at')->first();
				$photo = $event ? $event->getPrimaryPhoto() : null;
				$following = $signedIn ? ($user->getTagsFollowing()->contains($tag)) : false;
			@endphp
			<div class="bg-card border border-border rounded-lg overflow-hidden hover:border-primary transition-colors group">
				<!-- Tag Image -->
				<a href="/tags/{{ $tag->slug }}" class="block aspect-square relative overflow-hidden">
					@if($photo)
						<img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $tag->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
					@else
						<div class="w-full h-full flex items-center justify-center bg-muted">
							<i class="bi bi-tag text-4xl text-muted-foreground/30"></i>
						</div>
					@endif
				</a>

				<!-- Tag Info -->
				<div class="p-3">
					<div class="flex items-start justify-between gap-2">
						<div class="flex-1 min-w-0">
							<a href="/tags/{{ $tag->slug }}" class="block font-medium text-foreground hover:text-primary truncate text-sm">
								{{ $tag->name }}
							</a>
						</div>
						@if ($signedIn)
							@if ($following)
							<a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}"
								title="Unfollow"
								class="flex-shrink-0 text-primary hover:text-primary/70 transition-colors">
								<i class="bi bi-heart-fill"></i>
							</a>
							@else
							<a href="{!! route('tags.follow', ['id' => $tag->id]) !!}"
								title="Follow"
								class="flex-shrink-0 text-muted-foreground hover:text-primary transition-colors">
								<i class="bi bi-heart"></i>
							</a>
							@endif
						@endif
					</div>
				</div>
			</div>
		@endforeach
	</div>

	<!-- Bottom Pagination -->
	@if(isset($tags) && method_exists($tags, 'hasPages') && $tags->hasPages())
	<div class="flex justify-center">
		<div class="flex items-center gap-2">
			@if(!$tags->onFirstPage())
			<a href="{{ $tags->previousPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">&lt; Previous</a>
			@endif

			@if($tags->hasMorePages())
			<a href="{{ $tags->nextPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">Next &gt;</a>
			@endif
		</div>
	</div>
	@endif
</div>

@stop

@section('footer')
<script>
	// Popular tags toggle
	document.getElementById('popular-tags-toggle')?.addEventListener('click', function() {
		const content = document.getElementById('popular-tags-content');
		const chevron = document.getElementById('popular-chevron');
		content.classList.toggle('hidden');
		chevron.classList.toggle('rotate-180');
	});

	// Filters toggle
	document.getElementById('filters-toggle')?.addEventListener('click', function() {
		const content = document.getElementById('filters-content');
		const chevron = document.getElementById('filters-chevron');

		if (content.classList.contains('hidden')) {
			content.classList.remove('hidden');
			chevron.classList.remove('bi-chevron-right');
			chevron.classList.add('bi-chevron-down');
		} else {
			content.classList.add('hidden');
			chevron.classList.remove('bi-chevron-down');
			chevron.classList.add('bi-chevron-right');
		}
	});
</script>
@endsection
