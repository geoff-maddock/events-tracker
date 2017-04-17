@if (count($posts) > 0)

	@foreach ($posts as $post)
	<tbody class='thread-post'>
	<tr>
		<td colspan='2'>
		<td>{{ $post->user->name }} 
			@if ($signedIn && $post->ownedBy($user))
			<a href="{!! route('posts.edit', ['id' => $post->id]) !!}" title="Edit this post."><span class='glyphicon glyphicon-pencil'></span></a>
			@endif</td>
		<td></td>
		<td></td>
		<td>{{ $post->created_at->diffForHumans() }}</td>
	</tr>
	<tr>
		<td colspan='6' class="post-body">
		{{ $post->body }}
		<br>
			@unless ($post->entities->isEmpty())
			Related:
				@foreach ($post->entities as $entity)
					<span class="label label-tag"><a href="/posts/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
				@endforeach
			@endunless

			@unless ($post->tags->isEmpty())
			Tags:
				@foreach ($post->tags as $tag)
					<span class="label label-tag"><a href="/posts/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
				@endforeach
		@endunless		
		</td>
	</tr>
	</tbody>
	@endforeach

@else
	<tr>
	<td colspan="6"><i>No posts listed</i></td>
	</tr> 
@endif
