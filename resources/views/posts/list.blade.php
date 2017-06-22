@if (count($posts) > 0)

	@foreach ($posts as $post)
	<tbody class='thread-post'>
	<tr id='post-{{ $post->id }}'>
		<td></td>
		<td class="hidden-xs hidden-sm"></td>
		<td>
		    @if (isset($post->user))
		      @include('users.avatar', ['user' => $post->user])
		    {!! link_to_route('users.show', $post->user->name, [$thread->user->id], ['class' => 'forum-link']) !!} 
		    @else
		    User deleted
		    @endif
		</td>
		<td class="hidden-xs hidden-sm"></td>
		<td class="hidden-xs hidden-sm"></td>
		<td class="hidden-xs">{{ $post->created_at->diffForHumans() }}</td>
	</tr>
	<tr>
		<td colspan='6' class="post-body">
			<!-- TO DO: change this to storing the trust in the user at post save -->
			@if ($post->user->can('trust_post'))
				{!! $post->body !!}
			@else
				{{ $post->body }}
			@endcan
			<span>
			
			@if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
				<a href="{!! route('posts.edit', ['id' => $post->id]) !!}" title="Edit this post."><span class='glyphicon glyphicon-pencil text-primary'></span></a>
				{!! link_form_icon('glyphicon-trash text-warning', $post, 'DELETE', 'Delete the [post]') !!}
			@endif</span>

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

@section('scripts.footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  var type = $(this).data('type');
  swal({   
    title: "Are you sure?",
    text: "You will not be able to recover this "+type+"!", 
    type: "warning",   
    showCancelButton: true,   
    confirmButtonColor: "#DD6B55",
    confirmButtonText: "Yes, delete it!", 
    closeOnConfirm: true
  }, 
   function(isConfirm){
    if (isConfirm) {
        form.submit();
    };
   // 
  });
})
</script>
@stop
