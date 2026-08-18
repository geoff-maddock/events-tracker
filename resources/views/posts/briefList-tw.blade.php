@if (count($posts) > 0)
	@foreach ($posts as $post)
	<div id='post-{{ $post->id }}' class="bg-accent rounded-lg p-4 mb-3">
		<div class="flex items-start gap-3 mb-3">
			<div class="flex-shrink-0">
				@if (isset($post->user))
					@include('users.avatar', ['user' => $post->user])
				@else
					<div class="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
						<i class="bi bi-person text-muted-foreground"></i>
					</div>
				@endif
			</div>

			<div class="flex-1 min-w-0">
				<div class="flex items-center justify-between mb-2">
					<div class="text-sm text-muted-foreground">
						@if (isset($post->user))
							<span class="font-medium text-foreground">{{ $post->user->name }}</span>
							<span class="mx-1">•</span>
						@endif
						<span>{{ $post->created_at->diffForHumans() }}</span>
					</div>

					<div class="flex items-center gap-2">
						@if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
							<a href="{!! route('posts.edit', ['post' => $post->id]) !!}" title="Edit this post" class="text-muted-foreground hover:text-foreground">
								<i class="bi bi-pencil-fill"></i>
							</a>
							<form action="{{ route('posts.destroy', $post) }}" method="POST" class="inline" data-confirm="Are you sure you want to delete this post?">
								@csrf
								@method('DELETE')
								<button type="submit" class="text-muted-foreground hover:text-red-500" title="Delete the post">
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
								<a href="{!! route('posts.like', ['id' => $post->id]) !!}" title="Like" class="text-muted-foreground hover:text-yellow-500">
									<i class="bi bi-star"></i>
								</a>
							@endif
						@endif
					</div>
				</div>

				<div class="text-foreground mb-3">
					@if (isset($post->user) && $post->user->can('trust_post'))
						{!! $post->body !!}
					@else
						{{ $post->body }}
					@endif
				</div>

				@unless ($post->entities->isEmpty())
				<div class="mb-2 flex flex-wrap items-center gap-2">
					<span class="text-sm text-muted-foreground">Related:</span>
					@foreach ($post->entities as $entity)
						<x-entity-badge :entity="$entity" />
					@endforeach
				</div>
				@endunless

				@unless ($post->tags->isEmpty())
				<div class="flex flex-wrap items-center gap-2">
					<span class="text-sm text-muted-foreground">Tags:</span>
					@foreach ($post->tags as $tag)
						<x-tag-badge :tag="$tag" context="posts" />
					@endforeach
				</div>
				@endunless
			</div>
		</div>
	</div>
	@endforeach

@else
	<div class="text-center text-muted-foreground py-8">
		<p>No posts yet.</p>
	</div>
@endif
