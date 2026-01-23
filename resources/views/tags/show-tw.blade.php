@extends('layouts.app-tw')

@section('title')
@if (isset($tag))
{{ $tag }} • Tag
@else
Tags
@endif
@endsection

@section('content')

<div class="mx-auto px-6 py-8 max-w-[2400px]">
	<div class="space-y-6">
		<!-- Back Button -->
		<div class="flex items-center gap-4">
			<a href="{{ url('/tags') }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm border border-border rounded-lg hover:bg-accent transition-colors">
				<i class="bi bi-arrow-left"></i>
				Back to Tags
			</a>
		</div>

		<!-- Header Section -->
		<div class="flex items-start justify-between">
			<div>
				<h1 class="text-4xl font-bold text-foreground">
					@if (isset($tag))
					{{ $tag }}
					@else
					Tags
					@endif
				</h1>
				@if (isset($tagObject))
					@if ($tagObject->tagType)
					<p class="text-xl text-muted-foreground mt-1">{{ $tagObject->tagType->name }}</p>
					@endif
					@if ($tagObject->tag_definition)
					<p class="mt-2 text-muted-foreground">{{ $tagObject->tag_definition }}</p>
					@endif
				@endif
			</div>

			<!-- Actions (Follow + More Menu) -->
			@if ($signedIn && isset($tagObject))
			<div class="flex items-center gap-2">
				@php
					$following = $user->getTagsFollowing();
					$isFollowing = $following->contains($tagObject);
				@endphp

				<!-- Follow/Unfollow Star -->
				<a href="{{ $isFollowing ? route('tags.unfollow', ['id' => $tagObject->id]) : route('tags.follow', ['id' => $tagObject->id]) }}"
					class="p-2 rounded-md hover:bg-accent transition-colors"
					aria-label="{{ $isFollowing ? 'Unfollow' : 'Follow' }}"
					title="{{ $isFollowing ? 'Unfollow' : 'Follow' }}">
					<i class="bi {{ $isFollowing ? 'bi-star-fill text-yellow-500' : 'bi-star text-muted-foreground' }} text-xl"></i>
				</a>

				<!-- Edit/Delete Menu (if owner) -->
				@if ($tagObject->created_by && $user->id === $tagObject->created_by)
				<div class="relative group">
					<button class="p-2 rounded-md hover:bg-accent transition-colors text-muted-foreground hover:text-foreground">
						<i class="bi bi-three-dots"></i>
					</button>
					<div class="absolute right-0 mt-1 w-48 bg-card border border-border rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
						<div class="py-1">
							<a href="{{ route('tags.edit', $tagObject->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors">
								<i class="bi bi-pencil"></i>
								Edit Tag
							</a>
							<form method="POST" action="{{ route('tags.destroy', $tagObject->id) }}" class="inline-block w-full" onsubmit="return confirm('Are you sure you want to delete this tag?');">
								@csrf
								@method('DELETE')
								<button type="submit" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 transition-colors w-full text-left">
									<i class="bi bi-trash"></i>
									Delete Tag
								</button>
							</form>
						</div>
					</div>
				</div>
				@endif
			</div>
			@endif
		</div>

		<!-- Related Tags Section -->
		@if(isset($tagObject))
		<div>
			<h2 class="text-lg font-semibold mb-4 text-foreground">Related Tags</h2>
			@php
				// Get related tags - this would need to be added to the controller
				// For now, show tags from the same type
				$relatedTags = isset($tagObject->tagType)
					? App\Models\Tag::where('tag_type_id', $tagObject->tagType->id)
						->where('id', '!=', $tagObject->id)
						->limit(10)
						->get()
					: collect();
			@endphp
			@if($relatedTags->count() > 0)
			<div class="flex flex-wrap gap-2">
				@foreach($relatedTags as $relatedTag)
				<a href="/tags/{{ $relatedTag->slug }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-card border border-border text-foreground hover:bg-accent transition-colors">
					<span class="font-medium">{{ $relatedTag->name }}</span>
				</a>
				@endforeach
			</div>
			@else
			<p class="text-muted-foreground">No related tags found.</p>
			@endif
		</div>
		@endif

		<!-- Content Sections -->
		<div class="space-y-8">
			<!-- Events Section -->
			@if(isset($events) && count($events) > 0)
			<div>
				<div class="flex items-baseline gap-3 mb-4">
					<h2 class="text-2xl font-semibold text-foreground">Events</h2>
					<a href="{{ url('/events?filters[tag]=' . $slug) }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
						View all
					</a>
				</div>
				<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3 3xl:grid-cols-4">
					@foreach ($events->take(8) as $event)
					@include('events.card-tw', ['event' => $event])
					@endforeach
				</div>
			</div>
			@endif

			<!-- Entities Section -->
			@if(isset($entities) && count($entities) > 0)
			<div>
				<div class="flex items-baseline gap-3 mb-4">
					<h2 class="text-2xl font-semibold text-foreground">Entities</h2>
					<a href="{{ url('/entities?filters[tag]=' . $slug) }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
						View all
					</a>
				</div>
				<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3 3xl:grid-cols-4">
					@foreach ($entities->take(8) as $entity)
					@include('entities.card-tw', ['entity' => $entity])
					@endforeach
				</div>
			</div>
			@endif

			<!-- Series Section -->
			@if(isset($series) && count($series) > 0)
			<div>
				<div class="flex items-baseline gap-3 mb-4">
					<h2 class="text-2xl font-semibold text-foreground">Series</h2>
					<a href="{{ url('/series?filters[tag]=' . $slug) }}" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
						View all
					</a>
				</div>
				<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3 3xl:grid-cols-4">
					@foreach ($series->take(8) as $s)
					@include('series.card-tw', ['series' => $s])
					@endforeach
				</div>
			</div>
			@endif

			<!-- No Content Message -->
			@if((!isset($series) || count($series) == 0) && (!isset($events) || count($events) == 0) && (!isset($entities) || count($entities) == 0))
			<div class="text-center py-12 bg-card rounded-lg border border-border">
				<i class="bi bi-tag text-4xl text-muted-foreground/50 mb-3 block"></i>
				<p class="text-muted-foreground">No content found for this tag.</p>
			</div>
			@endif
		</div>
	</div>
</div>

@stop
