@if (count($posts) > 0)

	@foreach ($posts as $post)
	<tbody class='thread-post'>
	<tr id='post-{{ $post->id }}'>
		<td></td>
		<td class="hidden-xs hidden-sm"></td>
		<td>
		    @if (isset($post->user))
		      @include('users.avatar', ['user' => $post->user])
		      {!! link_to_route('users.show', $post->user->name, [$post->user->id], ['class' => 'forum-link']) !!}
		    @else
		    User deleted
		    @endif
		</td>
		<td class="hidden-xs hidden-sm"></td>
		<td class="hidden-xs hidden-sm"></td>
        <td class="cell-stat text-center hidden-xs hidden-sm">{{ $post->likes }}</td>
		<td class="hidden-xs">{{ $post->created_at->diffForHumans() }}</td>
	</tr>
	<tr>
		<td colspan='7' class="post-body">
			<!-- TO DO: change this to storing the trust in the user at post save -->
			<p>
			@if (isset($post->user) && $post->user->can('trust_post'))
				{!! $post->body !!}
			@else
				{{ $post->body }}
			@endcan
			</p>
			<p>
			@unless ($post->entities->isEmpty())
			Related:
				@foreach ($post->entities as $entity)
					<span class="label label-tag"><a href="/posts/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a>
                            <a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
                </span>
				@endforeach
			@endunless

			@unless ($post->tags->isEmpty())
			Tags:
				@foreach ($post->tags as $tag)
					<span class="label label-tag"><a href="/posts/tag/{{ $tag->slug }}">{{ $tag->name }}</a>
                            <a href="{!! route('tags.show', ['tag' => $tag->slug]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
                	</span>
				@endforeach
			@endunless
			</p>

			<div class="btn-group" role="group" aria-label="...">
			@if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
				<a href="{!! route('posts.edit', ['post' => $post->id]) !!}" class="btn btn-sm btn-default" title="Edit this post.">Edit <span class='glyphicon glyphicon-pencil text-primary'></span></a>
				{!! link_form_icon('glyphicon-trash text-warning', $post, 'DELETE', 'Delete the post', NULL, 'delete') !!}
			@endif
            @if ($signedIn)
                @if ($like = $post->likedBy($user))
                    <a href="{!! route('posts.unlike', ['id' => $post->id]) !!}" title="Click to unlike" class="btn btn-sm btn-default">Unlike <span class='glyphicon glyphicon-star text-success'></span></a>
                @else
                    <a href="{!! route('posts.like', ['id' => $post->id]) !!}" title="Click to like" class="btn btn-sm btn-default">Like <span class='glyphicon glyphicon-star-empty text-warning'></span></a>
                @endif
            @endif

            </div>

		</td>
	</tr>
	</tbody>
	@endforeach

@else
	<tr>
	<td colspan="7"><i>No posts listed</i></td>
	</tr>
@endif

@section('scripts.footer')
@stop
