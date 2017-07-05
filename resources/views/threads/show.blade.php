@extends('app')

@section('title','Forum')

@section('content')

	<h1>Forum
		@include('threads.crumbs')
	</h1>

	<p>
	<a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
	<a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paginated threads</a>
	<a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add an thread</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	@if (isset($thread) && count($thread) > 0)
	<div class="col-lg-12">
	<table class="table forum table-striped">
	    <thead>
	      <tr>
	        <th>Thread</th>
	        <th class="cell-stat hidden-xs hidden-sm">Category</th>
	        <th class="cell-stat">Author</th>
	        <th class="cell-stat text-center hidden-xs hidden-sm">Posts</th>
	        <th class="cell-stat text-center hidden-xs hidden-sm">Views</th>
	        <th class="cell-stat-2x hidden-xs">Last Post</th>
	      </tr>
	    </thead>
	<tbody>	
	<tr>
	<td>{!! link_to_route('threads.show', $thread->name, [$thread->id], ['class' => 'forum-link']) !!} 
			@if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
				<a href="{!! route('threads.edit', ['id' => $thread->id]) !!}" title="Edit this thread." class="hover-dim"><span class='glyphicon glyphicon-pencil text-primary'></span></a>
				{!! link_form_icon('glyphicon-trash text-warning', $thread, 'DELETE', 'Delete the [thread]') !!}
				@if (!$thread->is_locked) 
					<a href="{!! route('threads.lock', ['id' => $thread->id]) !!}" title="Lock this thread." class="hover-dim"><span class='material-icons md-18 icon-correct text-primary'>lock</span></a>
				@else 
					<a href="{!! route('threads.unlock', ['id' => $thread->id]) !!}" title="Thread is locked.  Click to unlock." class="hover-dim"><span class='material-icons md-18 icon-correct text-danger'>lock</span></a>
				@endif
				@if ($follow = $thread->followedBy($user))
					<a href="{!! route('threads.unfollow', ['id' => $thread->id]) !!}" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
				@else
					<a href="{!! route('threads.follow', ['id' => $thread->id]) !!}" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
				@endif
			@endif

			<br>
            @unless ($thread->series->isEmpty())
            Series:
                @foreach ($thread->series as $series)
                    <span class="label label-tag"><a href="/threads/series/{{ urlencode($series->slug) }}">{{ $series->name }}</a></span>
                @endforeach
            @endunless

			@unless ($thread->entities->isEmpty())
			Related:
				@foreach ($thread->entities as $entity)
					<span class="label label-tag"><a href="/threads/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
				@endforeach
			@endunless

			@unless ($thread->tags->isEmpty())
			Tags:
				@foreach ($thread->tags as $tag)
					<span class="label label-tag"><a href="/threads/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
				@endforeach
		@endunless

	</td>
    <td class="cell-stat hidden-xs hidden-sm">{{ $thread->thread_category or 'General'}}</td>
    <td class="cell-stat">
	    @if (isset($thread->user))
	      @include('users.avatar', ['user' => $thread->user])
	    {!! link_to_route('users.show', $thread->user->name, [$thread->user->id], ['class' => 'forum-link']) !!} 
	    @else
	    User deleted
    	@endif
    </td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->postCount }}</td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->views }}</td>
    <td class="cell-stat-2x hidden-xs">{{ $thread->lastPostAt->diffForHumans() }}</td>
    </tr>
    <tr>
    <td colspan="6">
    	<div style="padding-left: 5px;">
			<!-- TO DO: change this to storing the trust in the user at thread save -->
			@if ($thread->user->can('trust_thread'))
				{!! $thread->body !!}
			@else
				{{ $thread->body }}
			@endcan    	
    	</div>
    </td>
    </tr>
				@include('posts.list', ['thread' => $thread, 'posts' => $thread->posts])

	</tbody>
	</table>
	</div>

	<div class="col-lg-6">

		@if ($thread->is_locked)
		<P class="text-center">This thread has been locked.</P>
		@else
			@if ($signedIn)
			Add new post as <strong>{{ $user->name }}</strong>
			<form method="POST" action="{{ $thread->path().'/posts' }}">
			{{ csrf_field() }}
			<div class="form-group">
				<textarea name="body" id="body" class="form-control" placeholder="Have something to say?" rows="5"></textarea>
			</div>
			<button type="submit" class="btn btn-default">Post</button>
			</form>

			@else
			<p class="text-center">Please <a href="{{ url('/auth/login')}}">sign in</a> to participate in this discussion.</p>
			@endif
		@endif
	</div>
	@endif
	</div>
@stop
@section('scripts.footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  swal({   
    title: "Are you sure?",
    text: "You will not be able to recover this thread!", 
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
  });
})
</script>
@stop