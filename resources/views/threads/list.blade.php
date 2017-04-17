@if (count($threads) > 0)
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

	@foreach ($threads as $thread)

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
    <td>@if (isset($thread->threadCategory))
    <a class="thread-name btn" href="/threads/category/{{ urlencode($thread->threadCategory->name) }}">{{ $thread->threadCategory->name }}</a>
	@else
    General
    @endif
    </td>
    <td class="cell-stat hidden-xs hidden-sm">
    {!! link_to_route('users.show', $thread->user->name, [$thread->user->id], ['class' => 'thread-name btn']) !!} 
    </td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->postCount }}</td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->views }}</td>
    <td>{{ $thread->lastPostAt->diffForHumans() }}</td>
    </tr>

	@endforeach
</tbody>
</table>
@else
	<ul class='thread-list'><li style='clear:both;'><i>No threads listed</i></li></ul> 
@endif
