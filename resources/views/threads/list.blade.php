@if (count($threads) > 0)
<table class="table forum table-striped">
    <thead>
      <tr>
        <th>
          Threads
        </th>
        <th class="cell-stat d-none d-lg-table-cell">Category</th>
        <th class="cell-stat d-none d-sm-table-cell">User</th>
        <th class="cell-stat text-center thread-columns d-none d-md-table-cell">Posts</th>
        <th class="cell-stat text-center thread-columns d-none d-md-table-cell">Views</th>
        <th class="cell-stat thread-columns">Last Post</th>
      </tr>
    </thead>
<tbody>
	@foreach ($threads as $thread)

	<tr>
	<td>
    {!! link_to_route('threads.show', $thread->name, [$thread->id], ['id' => 'thread-'.$thread->id, 'title' => 'Topic: '.$thread->name, 'class' => 'forum-link']) !!}

    @if ($event = $thread->event)
        <a href="{!! route('events.show', ['event' => $event->id]) !!}" title="Show event."><span class='glyphicon glyphicon-calendar'></span></a>
    @endif

    @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
        <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}" title="Edit this thread."><i class="bi bi-pencil-fill icon"></i></a>
        {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning', $thread, 'DELETE', 'Delete the thread', NULL, 'delete') !!}
    @endif

    <br>
    <span class="d-none d-md-inline">
    @if ($event = $thread->event)
      Event:
      <span class="badge rounded-pill bg-dark"><a href="{!! route('events.show', ['event' => $event->id]) !!}">{{ Str::limit($event->name,30,' ...') }}</a></span>
    @endif

    @unless ($thread->series->isEmpty())
        Series:
        @foreach ($thread->series as $series)
            <span class="badge rounded-pill bg-dark"><a href="/threads/series/{{ urlencode($series->slug) }}">{{ $series->name }}</a>
                        <a href="{!! route('series.show', ['series' => $series->slug]) !!}" title="Show this series."><i class="bi bi-link-45deg text-info"></i></a>
                    </span>
        @endforeach
    @endunless

    @unless ($thread->entities->isEmpty())
    Related Entities:
      @foreach ($thread->entities as $entity)
        @include('entities.single_label')
      @endforeach
    @endunless

      @unless ($thread->tags->isEmpty())
      Tags:
        @foreach ($thread->tags as $tag)
          @include('tags.single_label')
        @endforeach
      @endunless
                  </span>
	</td>
    <td class="d-none d-lg-table-cell">@if (isset($thread->threadCategory))
    <a class="forum-link" href="/threads/category/{{ urlencode($thread->threadCategory->name) }}">{{ $thread->threadCategory->name }}</a>
	  @else
    General
    @endif
    </td>
    <td class="cell-stat d-none d-sm-table-cell text-nowrap">
      @if (isset($thread->user))
        @include('users.avatar', ['user' => $thread->user])
        <span class="cell-stat d-none d-xl-table-cell">
          {!! link_to_route('users.show', $thread->user->name, [$thread->user->id], ['class' => 'forum-link']) !!}
        </span>
      @else
         User deleted
      @endif

    </td>
    <td class="cell-stat text-center thread-columns d-none d-md-table-cell">{{ $thread->postCount }}</td>
    <td class="cell-stat text-center thread-columns d-none d-md-table-cell">{{ $thread->views }}</td>
    <td class="thread-columns text-nowrap">{{ $thread->lastPostAt->diffForHumans() }}</td>
    </tr>

	@endforeach
</tbody>
</table>
@else
	<ul class='event-list'><li class="flow-root"><i>No threads listed</i></li></ul>
@endif

