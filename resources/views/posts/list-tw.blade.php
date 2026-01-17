@if (count($posts) > 0)

	@foreach ($posts as $post)
	<tbody class='border-b border-border'>
	<tr id='post-{{ $post->id }}' class="hover:bg-accent/50 transition-colors">
		<td></td>
		<td class="hidden md:table-cell"></td>
		<td class="px-4 py-3">
		    @if (isset($post->user))
		      @include('users.avatar', ['user' => $post->user])
			  <span class="hidden md:inline">
		      {!! link_to_route('users.show', $post->user->name, [$post->user->id], ['class' => 'text-primary hover:underline']) !!}
			  </span>
		    @else
		    <span class="text-muted-foreground italic">User deleted</span>
		    @endif
		</td>
		<td class="hidden md:table-cell"></td>
		<td class="hidden md:table-cell"></td>
        <td class="px-4 py-3 text-center hidden md:table-cell">{{ $post->likes }}</td>
		<td class="px-4 py-3 hidden sm:table-cell text-sm text-muted-foreground">{{ $post->created_at->diffForHumans() }}</td>
	</tr>
	<tr>
		<td colspan='7' class="px-4 py-4 bg-accent/20">
			<div class="prose prose-sm max-w-none dark:prose-invert mb-3">
			@if (isset($post->user) && $post->user->can('trust_post'))
				{!! $post->body !!}
			@else
				{{ $post->body }}
			@endcan
			</div>

			@unless ($post->entities->isEmpty())
			<div class="mb-2">
				<span class="text-sm font-medium text-muted-foreground mr-2">Related:</span>
				<div class="inline-flex flex-wrap gap-1">
					@foreach ($post->entities as $entity)
						<a href="/posts/related-to/{{ urlencode($entity->slug) }}"
						   class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
							{{ $entity->name }}
							<a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show this entity." class="ml-1">
								<i class="bi bi-link-45deg"></i>
							</a>
						</a>
					@endforeach
				</div>
			</div>
			@endunless

			@unless ($post->tags->isEmpty())
			<div class="mb-3">
				<span class="text-sm font-medium text-muted-foreground mr-2">Tags:</span>
				<div class="inline-flex flex-wrap gap-1">
					@foreach ($post->tags as $tag)
						<x-tag-badge :tag="$tag" context="posts" />
					@endforeach
				</div>
			</div>
			@endunless

			<div class="flex items-center gap-2 flex-wrap">
			@if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
				<a href="{!! route('posts.edit', ['post' => $post->id]) !!}"
				   class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors"
				   title="Edit this post">
					Edit <i class="bi bi-pencil-fill ml-1"></i>
				</a>
				{!! link_form_bootstrap_icon('bi bi-trash-fill text-destructive', $post, 'DELETE', 'Delete the post', NULL, 'py-0 my-0', 'confirm') !!}
			@endif
            @if ($signedIn)
                @if ($like = $post->likedBy($user))
                    <a href="{!! route('posts.unlike', ['id' => $post->id]) !!}"
					   class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors"
					   title="Click to unlike">
						Unlike <i class="bi bi-star-fill ml-1"></i>
					</a>
                @else
                    <a href="{!! route('posts.like', ['id' => $post->id]) !!}"
					   class="inline-flex items-center px-2 py-1 text-sm bg-card border border-border rounded hover:bg-accent transition-colors"
					   title="Click to like">
						Like <i class="bi bi-star ml-1"></i>
					</a>
                @endif
            @endif
            </div>

		</td>
	</tr>
	</tbody>
	@endforeach

@else
	<tr>
	<td colspan="7" class="px-4 py-8 text-center text-muted-foreground italic">No posts listed</td>
	</tr>
@endif

@section('scripts.footer')
@stop
