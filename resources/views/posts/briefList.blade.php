@if (count($posts) > 0)

	@foreach ($posts as $post)
    <tbody>
	<tr id='post-{{ $post->id }}'>
		<td></td>
		<td>
		    @if (isset($post->user))
		      @include('users.avatar', ['user' => $post->user])
		    @else
		    -
		    @endif
		</td>

		<td class="hidden-xs">{{ $post->created_at->diffForHumans() }}</td>
	</tr>
	<tr>
		<td colspan='6' class="post-body p-2">
			<!-- TO DO: change this to storing the trust in the user at post save -->
			<span class="p-2">
			@if (isset($post->user) && $post->user->can('trust_post'))
				{!! $post->body !!}
			@else
				{{ $post->body }}
			@endcan
			</span>
			<span>

			@if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
				<a href="{!! route('posts.edit', ['post' => $post->id]) !!}" title="Edit this post."><i class="bi bi-pencil-fill icon"></i></a>
				{!! link_form_bootstrap_icon('bi bi-trash-fill icon', $post, 'DELETE', 'Delete the [post]') !!}
			@endif
            @if ($signedIn)
                @if ($like = $post->likedBy($user))
                    <a href="{!! route('posts.unlike', ['id' => $post->id]) !!}" title="Click to unlike"><i class="bi bi-star-fill icon"></i></a>
                @else
                    <a href="{!! route('posts.like', ['id' => $post->id]) !!}" title="Click to like"><i class="bi bi-star icon"></i></a>
                @endif
            @endif
            </span>

		<br>

			@unless ($post->entities->isEmpty())
			Related:
				@foreach ($post->entities as $entity)
					<span class="badge rounded-pill bg-dark"><a href="/posts/related-to/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
				@endforeach
			@endunless

			@unless ($post->tags->isEmpty())
			Tags:
				@foreach ($post->tags as $tag)
					<span class="badge rounded-pill bg-dark"><a href="/posts/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
				@endforeach
			@endunless
		</td>
	</tr>
	</tbody>
	@endforeach

@else
<tr>
	<td colspan="6">
		<small class="text-muted p-2">No posts in thread.</small>
	</td>
</tr>
@endif

