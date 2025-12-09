@if (count($posts) > 0)
	@foreach ($posts as $post)
	<div id='post-{{ $post->id }}' class="bg-dark-border rounded-lg p-4 mb-3">
		<div class="flex items-start gap-3 mb-3">
			<div class="flex-shrink-0">
				@if (isset($post->user))
					@include('users.avatar', ['user' => $post->user])
				@else
					<div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center">
						<i class="bi bi-person text-gray-400"></i>
					</div>
				@endif
			</div>
			
			<div class="flex-1 min-w-0">
				<div class="flex items-center justify-between mb-2">
					<div class="text-sm text-gray-400">
						@if (isset($post->user))
							<span class="font-medium text-white">{{ $post->user->name }}</span>
							<span class="mx-1">•</span>
						@endif
						<span>{{ $post->created_at->diffForHumans() }}</span>
					</div>
					
					<div class="flex items-center gap-2">
						@if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
							<a href="{!! route('posts.edit', ['post' => $post->id]) !!}" title="Edit this post" class="text-gray-400 hover:text-white">
								<i class="bi bi-pencil-fill"></i>
							</a>
							<form action="{{ route('posts.destroy', $post) }}" method="POST" class="inline">
								@csrf
								@method('DELETE')
								<button type="submit" class="text-gray-400 hover:text-red-500" onclick="return confirm('Are you sure you want to delete this post?')" title="Delete the post">
									<i class="bi bi-trash-fill"></i>
								</button>
							</form>
						@endif
						
						@if ($signedIn)
							@if ($like = $post->likedBy($user))
								<a href="{!! route('posts.unlike', ['id' => $post->id]) !!}" title="Unlike" class="text-yellow-500 hover:text-yellow-400">
									<i class="bi bi-star-fill"></i>
								</a>
							@else
								<a href="{!! route('posts.like', ['id' => $post->id]) !!}" title="Like" class="text-gray-400 hover:text-yellow-500">
									<i class="bi bi-star"></i>
								</a>
							@endif
						@endif
					</div>
				</div>

				<div class="text-gray-300 mb-3">
					@if (isset($post->user) && $post->user->can('trust_post'))
						{!! $post->body !!}
					@else
						{{ $post->body }}
					@endcan
				</div>

				@unless ($post->entities->isEmpty())
				<div class="mb-2">
					<span class="text-sm text-gray-400">Related:</span>
					<div class="inline-flex flex-wrap gap-2">
						@foreach ($post->entities as $entity)
						<a href="/posts/related-to/{{ urlencode($entity->slug) }}" class="inline-flex items-center px-2 py-1 bg-dark-card border border-dark-border text-white rounded text-sm hover:bg-dark-border">
							{{ $entity->name }}
						</a>
						@endforeach
					</div>
				</div>
				@endunless

				@unless ($post->tags->isEmpty())
				<div class="flex flex-wrap gap-2">
					@foreach ($post->tags as $tag)
					<a href="/posts/tag/{{ urlencode($tag->name) }}" class="inline-flex items-center px-2 py-1 bg-primary/20 border border-primary/30 text-primary rounded text-sm hover:bg-primary/30">
						<i class="bi bi-tag mr-1"></i>
						{{ $tag->name }}
					</a>
					@endforeach
				</div>
				@endunless
			</div>
		</div>
	</div>
	@endforeach

@else
	<div class="text-center text-gray-400 py-8">
		<p>No posts yet.</p>
	</div>
@endif
