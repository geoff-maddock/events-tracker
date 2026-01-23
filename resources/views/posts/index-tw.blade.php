@extends('layouts.app-tw')

@section('title','Posts')

@section('content')

<div class="flex flex-col gap-6">
	<!-- Header & Actions -->
	<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
		<h1 class="text-2xl font-bold text-foreground">Forum . Latest Posts</h1>
		<div class="flex flex-wrap gap-2">
			<x-ui.button variant="secondary" href="{{ url('/threads/all') }}">
				<i class="bi bi-list mr-2"></i>
				Show all threads
			</x-ui.button>
			<x-ui.button variant="secondary" href="{{ URL::route('threads.index') }}">
				<i class="bi bi-card-list mr-2"></i>
				Show paged threads
			</x-ui.button>
			<x-ui.button variant="default" href="{{ URL::route('threads.create') }}">
				<i class="bi bi-plus-circle mr-2"></i>
				Add a thread
			</x-ui.button>
		</div>
	</div>

	<!-- Filters Section -->
	<div class="mb-6">
		<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-colors">
			<i class="bi bi-funnel mr-2"></i>
			<span id="filters-toggle-text">@if($hasFilter) Hide @else Show @endif Filters</span>
			<i class="bi bi-chevron-down ml-2 transition-transform @if($hasFilter) rotate-180 @endif" id="filters-chevron"></i>
		</button>

		<!-- Active Filters / Reset -->
		@if($hasFilter)
		<div class="inline-flex items-center gap-2 ml-4">
			<a href="{{ url()->action('PostsController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
				Clear All <i class="bi bi-x ml-1"></i>
			</a>
		</div>
		@endif
	</div>

	<!-- Filter Panel -->
	<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6">
		{!! Form::open(['route' => [$filterRoute ?? 'posts.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<!-- Body Filter -->
			<div>
				<label for="filter_body" class="block text-sm font-medium text-muted-foreground mb-1">Body</label>
				<input type="text"
					name="filters[body]"
					id="filter_body"
					value="{{ $filters['body'] ?? '' }}"
					class="form-input-tw"
					placeholder="Search post content...">
			</div>

			<!-- User Filter -->
			<div>
				<label for="filter_user" class="block text-sm font-medium text-muted-foreground mb-1">User</label>
				{!! Form::select('filter_user', $userOptions, ($filters['user'] ?? null),
				[
					'data-theme' => 'tailwind',
					'data-allow-clear' => 'true',
					'class' => 'form-select-tw select2',
					'data-placeholder' => 'Select a user',
					'name' => 'filters[user]',
					'id' => 'filter_user'
				])
				!!}
			</div>

			<!-- Tag Filter -->
			<div>
				<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tag</label>
				{!! Form::select('filter_tag', $tagOptions, ($filters['tag'] ?? null),
				[
					'data-theme' => 'tailwind',
					'data-allow-clear' => 'true',
					'class' => 'form-select-tw select2',
					'data-placeholder' => 'Select a tag',
					'name' => 'filters[tag]',
					'id' => 'filter_tag'
				])
				!!}
			</div>
		</div>

		<!-- Filter Actions -->
		<div class="flex gap-2 mt-4">
			<button type="submit" class="px-4 py-2 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-colors">
				Apply
			</button>
			{!! Form::close() !!}
			{!! Form::open(['route' => ['posts.reset'], 'method' => 'GET']) !!}
			<button type="submit" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
				Reset
			</button>
			{!! Form::close() !!}
		</div>
	</div>

	<!-- Sort Controls -->
	<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
		<div class="text-sm text-muted-foreground">
			@if(isset($posts))
			Showing {{ $posts->firstItem() ?? 0 }} to {{ $posts->lastItem() ?? 0 }} of {{ $posts->total() }} results
			@endif
		</div>

		<div class="flex flex-wrap items-center gap-4">
			<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
				<a href="{{ url()->action('PostsController@rppReset') }}" class="px-3 py-2 bg-card border border-border rounded-lg hover:bg-accent transition-colors" title="Reset sort and filters">
					<i class="bi bi-arrow-clockwise"></i>
				</a>
				<select name="limit" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($limitOptions as $value => $label)
					<option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
				<span class="text-muted-foreground text-sm">Sort by:</span>
				<select name="sort" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($sortOptions as $value => $label)
					<option value="{{ $value }}" {{ ($sort ?? 'posts.created_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
				<select name="direction" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($directionOptions as $value => $label)
					<option value="{{ $value }}" {{ ($direction ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
			</form>
		</div>
	</div>

	<!-- Posts Table -->
	@if (count($posts) > 0)
		<div class="rounded-lg border bg-card shadow overflow-hidden">
			<div class="overflow-x-auto">
				<table class="w-full">
					<thead class="bg-primary/10 border-b border-border">
						<tr class="text-sm font-semibold text-muted-foreground">
							<th class="px-4 py-3 text-left">User</th>
							<th class="px-4 py-3 text-left hidden md:table-cell">Thread</th>
							<th class="px-4 py-3 text-left hidden md:table-cell">Category</th>
							<th class="px-4 py-3 text-center hidden md:table-cell">Likes</th>
							<th class="px-4 py-3 text-left hidden sm:table-cell">Last Post</th>
						</tr>
					</thead>
					@foreach ($posts as $post)
						<tbody class='border-b border-border'>
							<tr id='post-{{ $post->id }}' class="hover:bg-accent/50 transition-colors">
								<td class="px-4 py-3">
									@if (isset($post->user))
										@include('users.avatar', ['user' => $post->user])
										{!! link_to_route('users.show', $post->user->name, [$post->user->id], ['class' => 'text-primary hover:underline']) !!}
									@else
										<span class="text-muted-foreground italic">User deleted</span>
									@endif
								</td>
								<td class="px-4 py-3 hidden md:table-cell">
									{!! link_to_route('threads.show', $post->thread->name, [$post->thread ? $post->thread->id : 0], ['class' => 'text-primary hover:underline']) !!}
								</td>
								<td class="px-4 py-3 hidden md:table-cell">
									{{ $post->thread->threadCategory ? $post->thread->threadCategory->name : 'General' }}
								</td>
								<td class="px-4 py-3 text-center hidden md:table-cell">{{ $post->likes }}</td>
								<td class="px-4 py-3 hidden sm:table-cell text-sm text-muted-foreground">{{ $post->created_at->diffForHumans() }}</td>
							</tr>
							<tr>
								<td colspan='5' class="px-4 py-4 bg-accent/20">
									<div class="prose prose-sm max-w-none dark:prose-invert mb-3">
										@if (isset($post->user) && $post->user->can('trust_post'))
											{!! $post->body !!}
										@else
											{{ $post->body }}
										@endcan
									</div>

									<div class="flex items-center gap-2 flex-wrap mb-3">
										@if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
											<a href="{!! route('posts.edit', ['post' => $post->id]) !!}"
											   class="text-sm text-muted-foreground hover:text-primary transition-colors"
											   title="Edit this post">
												<i class="bi bi-pencil-fill mr-1"></i>Edit
											</a>
											{!! link_form_bootstrap_icon('bi bi-trash-fill text-destructive', $post, 'DELETE', 'Delete the post', NULL, 'delete') !!}
										@endif
										@if ($signedIn)
											@if ($like = $post->likedBy($user))
												<a href="{!! route('posts.unlike', ['id' => $post->id]) !!}"
												   class="text-sm text-primary hover:text-primary/80 transition-colors"
												   title="Click to unlike">
													<i class="bi bi-star-fill mr-1"></i>Unlike
												</a>
											@else
												<a href="{!! route('posts.like', ['id' => $post->id]) !!}"
												   class="text-sm text-muted-foreground hover:text-primary transition-colors"
												   title="Click to like">
													<i class="bi bi-star mr-1"></i>Like
												</a>
											@endif
										@endif
									</div>

									@unless ($post->entities->isEmpty())
										<div class="mb-2">
											<span class="text-sm font-medium text-muted-foreground mr-2">Related:</span>
											<div class="inline-flex flex-wrap gap-1">
												@foreach ($post->entities as $entity)
													<a href="/posts/related-to/{{ urlencode($entity->slug) }}"
													   class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
														{{ $entity->name }}
													</a>
												@endforeach
											</div>
										</div>
									@endunless

									@unless ($post->tags->isEmpty())
										<div>
											<span class="text-sm font-medium text-muted-foreground mr-2">Tags:</span>
											<div class="inline-flex flex-wrap gap-1">
												@foreach ($post->tags as $tag)
													<x-tag-badge :tag="$tag" context="posts" />
												@endforeach
											</div>
										</div>
									@endunless
								</td>
							</tr>
						</tbody>
					@endforeach
				</table>
			</div>

			<!-- Pagination -->
			<div class="px-4 py-3 border-t border-border">
				{!! $posts->onEachSide(2)->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	@else
		<div class="text-center py-12 bg-card rounded-lg border border-border">
			<i class="bi bi-chat-text text-4xl text-muted-foreground/50 mb-3 block"></i>
			<p class="text-muted-foreground italic">No posts listed</p>
		</div>
	@endif
</div>
@stop

@section('footer')
<script>
	// Filter toggle functionality
	document.getElementById('filters-toggle-btn')?.addEventListener('click', function() {
		const panel = document.getElementById('filter-panel');
		const text = document.getElementById('filters-toggle-text');
		const chevron = document.getElementById('filters-chevron');

		panel.classList.toggle('hidden');

		if (panel.classList.contains('hidden')) {
			text.textContent = 'Show Filters';
			chevron.classList.remove('rotate-180');
		} else {
			text.textContent = 'Hide Filters';
			chevron.classList.add('rotate-180');
		}
	});
</script>
@include('partials.filter-js')
@endsection
