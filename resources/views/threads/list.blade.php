@if (count($threads) > 0)
<table class="table forum table-striped">
    <thead>
      <tr>
        <th>
          Threads
        </th>
        <th class="cell-stat ">Category</th>
        <th class="cell-stat hidden-xs hidden-sm">Users</th>
        <th class="cell-stat text-center hidden-xs hidden-sm">Posts</th>
        <th class="cell-stat text-center hidden-xs hidden-sm">Views</th>
        <th class="cell-stat ">Last Post</th>
      </tr>
    </thead>
<tbody>
	@foreach ($threads as $thread)

	<tr>
	<td>
    {!! link_to_route('threads.show', $thread->name, [$thread->id], ['id' => 'thread-name', 'title' => 'Thread topic.', 'class' => 'forum-link']) !!}

    @if ($event = $thread->event)
        <a href="{!! route('events.show', ['event' => $event->id]) !!}" title="Show event."><span class='glyphicon glyphicon-calendar'></span></a>
    @endif

    @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
        <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}" title="Edit this thread."><span class='glyphicon glyphicon-pencil'></span></a>
        {!! link_form_icon('glyphicon-trash text-warning', $thread, 'DELETE', 'Delete the thread', NULL, 'delete') !!}
    @endif

    <br>

    @unless ($thread->series->isEmpty())
    Series:
        @foreach ($thread->series as $series)
            <span class="label label-tag"><a href="/threads/series/{{ urlencode($series->slug) }}" class="label-link">{{ $series->name }}</a>
                <a href="{!! route('series.show', ['series' => $series->id]) !!}" title="Show this series."><span class='glyphicon glyphicon-link text-info'></span></a>
            </span>
        @endforeach
    @endunless

			@unless ($thread->entities->isEmpty())
			Related:
				@foreach ($thread->entities as $entity)
					<span class="label label-tag"><a href="/threads/relatedto/{{ urlencode($entity->slug) }}" class="label-link">{{ $entity->name }}</a>
                        <a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
                    </span>
				@endforeach
			@endunless

			@unless ($thread->tags->isEmpty())
			Tags:
				@foreach ($thread->tags as $tag)
					<span class="label label-tag"><a href="/threads/tag/{{ $tag->slug }}" class="label-link">{{ $tag->name }}</a>
                        <a href="{!! route('tags.show', ['tag' => $tag->slug]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
                    </span>
				@endforeach
		@endunless

	</td>
    <td>@if (isset($thread->threadCategory))
    <a class="forum-link" href="/threads/category/{{ urlencode($thread->threadCategory->name) }}">{{ $thread->threadCategory->name }}</a>
	@else
    General
    @endif
    </td>
    <td class="cell-stat hidden-xs hidden-sm">
      @if (isset($thread->user))
        @include('users.avatar', ['user' => $thread->user])
      {!! link_to_route('users.show', $thread->user->name, [$thread->user->id], ['class' => 'forum-link']) !!}
      @else
      User deleted
      @endif
    </td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->postCount }}</td>
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->views }}</td>
    <td>{{ $thread->lastPostAt->diffForHumans() }}</td>
    </tr>

	@endforeach
</tbody>
</table>
@else
	<ul class='event-list'><li style='clear:both;'><i>No threads listed</i></li></ul>
@endif

@section('scripts.footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  var type = $(this).data('type');
  Swal.fire({
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
