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
	        <th>
	          <h3>Topic</h3>
	        </th>
	        <th class="cell-stat hidden-xs hidden-sm">Category</th>
	        <th class="cell-stat hidden-xs hidden-sm">Users</th>
	        <th class="cell-stat text-center hidden-xs hidden-sm">Posts</th>
	        <th class="cell-stat text-center hidden-xs hidden-sm">Views</th>
	        <th class="cell-stat-2x hidden-xs hidden-sm">Last Post</th>
	      </tr>
	    </thead>
	<tbody>	
	<tr>
	<td>{!! link_to_route('threads.show', $thread->name, [$thread->id], ['class' => 'thread-name btn']) !!} 
			@if ($signedIn && $thread->ownedBy($user))
			<a href="{!! route('threads.edit', ['id' => $thread->id]) !!}" title="Edit this thread."><span class='glyphicon glyphicon-pencil'></span></a>
			@endif
			<br>
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
    <td>{{ $thread->thread_category or 'General'}}</td>
    <td class="cell-stat hidden-xs hidden-sm">{{ $thread->user->name }}</td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->postCount }}</td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->views }}</td>
    <td>{{ $thread->lastPostAt->diffForHumans() }}</td>
    </tr>
    <tr>
    <td colspan="6">
    	<div style="padding-left: 20px;">
    		{{ $thread->body }}
    		<br>
    		@if ($signedIn && $thread->ownedBy($user))
				{!! Form::open(['route' => ['threads.destroy', 'id' => $thread->id], 'method' => 'delete','class' => 'delete']) !!}
    			<button type="submit" class="btn btn-danger btn-mini">Delete</button>
				{!! Form::close() !!}
			@endif
    	</div>
    </td>
    </tr>
				@include('posts.list', ['thread' => $thread, 'posts' => $thread->posts])

	</tbody>
	</table>
	</div>

	<div class="col-lg-12">
	Add new post form here
	</div>
	@endif
	</div>
@stop
 
 <script>
    $(".delete").on("submit", function(){
        return confirm("Do you want to delete this item?");
    });
</script>