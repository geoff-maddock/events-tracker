@extends('app')

@section('title')
@if (isset($tag))
Keyword Tag • {{ $tag }}
@else
Keyword Tags
@endif
@endsection

@section('content')

<div class="flex flex-col lg:flex-row gap-6">
	<!-- Left Sidebar - Tag List -->
	<div class="w-full lg:w-64 flex-shrink-0">
		<div class="card-tw sticky top-4">
			<!-- Header -->
			<div class="p-4 border-b border-dark-border flex items-center justify-between">
				<h2 class="text-lg font-bold text-white">Keywords</h2>
				<button id="tag-list-toggle" class="text-gray-400 hover:text-white">
					<i class="bi bi-eye-fill"></i>
				</button>
			</div>

			<!-- Alphabetical Navigation + Tag List -->
			<div id="tag-list-content" class="p-4">
				<!-- Alphabet Quick Links -->
				<div class="grid grid-cols-6 gap-1 mb-4 text-xs">
					@foreach(range('A', 'Z') as $letter)
					<a href="#{{ $letter }}" class="text-center py-1 px-2 text-gray-400 hover:text-primary hover:bg-dark-card rounded transition-colors">{{ $letter }}</a>
					@endforeach
				</div>

				<!-- Tag List -->
				<div class="space-y-1 max-h-96 overflow-y-auto">
					@if ($signedIn)
					@php 
						$following = $user->getTagsFollowing();
					@endphp 
					@endif

					@foreach ($tags as $t)
					<div class="flex items-center justify-between py-1 {{ (isset($tag) && (strtolower($slug) === strtolower($t->slug))) ? 'bg-dark-card rounded px-2' : '' }}" id="{{ $t->name[0] }}">
						<a href="/tags/{{ $t->slug }}" 
							class="flex-1 text-sm {{ (isset($tag) && (strtolower($slug) === strtolower($t->slug))) ? 'text-primary font-semibold' : 'text-gray-300 hover:text-white' }}"
							title="Click to show all related events and entities">
							{{ $t->name }}
						</a>
						@if ($signedIn)
							@if ($following->contains($t))
							<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" 
								data-target="#tag-{{ $t->id }}" 
								title="Click to unfollow"
								class="ml-2 text-primary hover:text-primary-hover">
								<i class="bi bi-check-circle-fill"></i>
							</a>
							@else
							<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" 
								data-target="#tag-{{ $t->id }}" 
								title="Click to follow"
								class="ml-2 text-gray-500 hover:text-primary">
								<i class="bi bi-plus-circle"></i>
							</a>
							@endif
						@endif
					</div>
					@endforeach
				</div>
			</div>
		</div>
	</div>

	<!-- Main Content Area -->
	<div class="flex-1">
		<!-- Page Header -->
		<div class="mb-6">
			<h1 class="text-3xl font-bold text-primary mb-2">Keyword Tags</h1>
			<p class="text-gray-400">Browse and follow tags to discover related content.</p>
		</div>

		<!-- Action Menu -->
		<div class="mb-6 flex flex-wrap gap-2">
			<a href="{{ url('/tags') }}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
				Show All Tags
			</a>
			<a href="{!! URL::route('tags.create') !!}" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
				<i class="bi bi-plus-lg mr-2"></i>
				Add Tag
			</a>
		</div>

		<!-- Info Card (when no tag selected) -->
		@if (!isset($tag))
		<div class="card-tw mb-6">
			<div class="p-4 border-b border-dark-border">
				<h2 class="text-lg font-semibold text-white">Info</h2>
			</div>
			<div class="p-4 text-gray-300">
				<p>Click on a <strong class="text-white">keyword</strong> tag name in the left panel to find all related events or entities.</p>
				@if (Auth::guest())
				<p class="mt-2"><a href="{{ url('/login') }}" class="text-primary hover:text-primary-hover">Log in</a> so you can subscribe to tags for updates.</p>
				@else
				<p class="mt-2">Click on the <strong class="text-white">plus</strong> next to the tag to follow, <strong class="text-white">check</strong> to unfollow.</p>
				@endif
			</div>
		</div>
		@endif

		<!-- Recently Popular Tags -->
		@if (isset($latestTags))
		<div class="card-tw mb-6">
			<div class="p-4 border-b border-dark-border flex items-center justify-between">
				<h2 class="text-lg font-semibold text-white">Recently Popular Tags</h2>
				<button class="text-gray-400 hover:text-white" onclick="toggleSection('tag-popular')">
					<i class="bi bi-eye-fill"></i>
				</button>
			</div>
			<div id="tag-popular" class="p-4">
				<div class="flex flex-wrap gap-2">
					@foreach($latestTags as $t)
					<a href="/tags/{{ $t->slug }}" class="badge-tw badge-primary-tw hover:bg-primary/30">
						{{ $t->name }}
					</a>
					@endforeach
				</div>
			</div>
		</div>
		@endif

		<!-- Tags You Follow -->
		@if (!isset($match) && isset($userTags) && count($userTags) > 0)
		<div class="card-tw mb-6">
			<div class="p-4 border-b border-dark-border flex items-center justify-between">
				<h2 class="text-lg font-semibold text-white">Tags You Follow</h2>
				<button class="text-gray-400 hover:text-white" onclick="toggleSection('tag-followed')">
					<i class="bi bi-eye-fill"></i>
				</button>
			</div>
			<div id="tag-followed" class="p-4">
				<div class="flex flex-wrap gap-2">
					@foreach($userTags as $t)
					<a href="/tags/{{ $t->slug }}" class="badge-tw badge-primary-tw hover:bg-primary/30">
						{{ $t->name }}
					</a>
					@endforeach
				</div>
			</div>
		</div>

		<!-- Entities for followed tags -->
		<div class="card-tw mb-6">
			<div class="p-4 border-b border-dark-border flex items-center justify-between">
				<h2 class="text-lg font-semibold text-white">Entities</h2>
				<button class="text-gray-400 hover:text-white" onclick="toggleSection('tag-entity')">
					<i class="bi bi-eye-fill"></i>
				</button>
			</div>
			<div id="tag-entity" class="p-4">
				@if(isset($entities) && count($entities) > 0)
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
					@foreach ($entities as $entity)
					@include('entities.card-tw', ['entity' => $entity])
					@endforeach
				</div>
				<div class="mt-4">
					{!! $entities->onEachSide(2)->links('vendor.pagination.tailwind') !!}
				</div>
				@else
				<p class="text-gray-400 text-center py-8">No entities found.</p>
				@endif
			</div>
		</div>
		@endif

		<!-- Selected Tag Content -->
		@if (isset($match))
		<div class="space-y-6">
			<!-- Tag Info -->
			<div class="card-tw">
				<div class="p-4 border-b border-dark-border">
					<h2 class="text-2xl font-semibold text-white">{{ $match->name }}</h2>
				</div>
				<div class="p-4 text-gray-300">
					@if($match->tag_definition)
					<p>{{ $match->tag_definition }}</p>
					@endif
				</div>
			</div>

			<!-- Related Events -->
			@if(isset($events) && count($events) > 0)
			<div class="card-tw">
				<div class="p-4 border-b border-dark-border flex items-center justify-between">
					<h2 class="text-lg font-semibold text-white">Related Events</h2>
					<button class="text-gray-400 hover:text-white" onclick="toggleSection('tag-events')">
						<i class="bi bi-eye-fill"></i>
					</button>
				</div>
				<div id="tag-events" class="p-4">
					<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
						@foreach ($events as $event)
						@include('events.card-tw', ['event' => $event])
						@endforeach
					</div>
					<div class="mt-4">
						{!! $events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
					</div>
				</div>
			</div>
			@endif

			<!-- Related Entities -->
			@if(isset($entities) && count($entities) > 0)
			<div class="card-tw">
				<div class="p-4 border-b border-dark-border flex items-center justify-between">
					<h2 class="text-lg font-semibold text-white">Related Entities</h2>
					<button class="text-gray-400 hover:text-white" onclick="toggleSection('tag-entities')">
						<i class="bi bi-eye-fill"></i>
					</button>
				</div>
				<div id="tag-entities" class="p-4">
					<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
						@foreach ($entities as $entity)
						@include('entities.card-tw', ['entity' => $entity])
						@endforeach
					</div>
					<div class="mt-4">
						{!! $entities->onEachSide(2)->links('vendor.pagination.tailwind') !!}
					</div>
				</div>
			</div>
			@endif
		</div>
		@endif
	</div>
</div>

@stop

@section('footer')
<script>
	// Toggle tag list visibility
	document.getElementById('tag-list-toggle')?.addEventListener('click', function() {
		const content = document.getElementById('tag-list-content');
		content.classList.toggle('hidden');
	});

	// Toggle section visibility
	function toggleSection(sectionId) {
		const section = document.getElementById(sectionId);
		if (section) {
			section.classList.toggle('hidden');
		}
	}
</script>
@endsection
