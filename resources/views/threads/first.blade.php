<tr>
    <td>{!! link_to_route('threads.show', $thread->name, [$thread->id], ['class' => 'forum-link']) !!}
        @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
            <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}" title="Edit this thread." class="hover-dim"><span class='glyphicon glyphicon-pencil text-primary'></span></a>
            {!! link_form_icon('glyphicon-trash text-warning', $thread, 'DELETE', 'Delete the [thread]') !!}
            @if (!$thread->is_locked)
                <a href="{!! route('threads.lock', ['id' => $thread->id]) !!}" title="Lock this thread." class="hover-dim"><span class='material-icons md-18 icon-correct text-primary'>lock</span></a>
            @else
                <a href="{!! route('threads.unlock', ['id' => $thread->id]) !!}" title="Thread is locked.  Click to unlock." class="hover-dim"><span class='material-icons md-18 icon-correct text-danger'>lock</span></a>
            @endif
        @endif
        @if ($signedIn)
            @if ($follow = $thread->followedBy($user))
                <a href="{!! route('threads.unfollow', ['id' => $thread->id]) !!}" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
            @else
                <a href="{!! route('threads.follow', ['id' => $thread->id]) !!}" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
            @endif
            @if ($like = $thread->likedBy($user))
                <a href="{!! route('threads.unlike', ['id' => $thread->id]) !!}" title="Click to unlike"><span class='glyphicon glyphicon-star text-success'></span></a>
            @else
                <a href="{!! route('threads.like', ['id' => $thread->id]) !!}" title="Click to like"><span class='glyphicon glyphicon-star-empty text-warning'></span></a>
            @endif
        @endif
        <br>

        @if ($event = $thread->event)
            Event:
            <span class="label label-tag"><a href="{!! route('events.show', ['event' => $event->id]) !!}">{{ $event->name }}</a></span>
        @endif

        @unless ($thread->series->isEmpty())
            Series:
            @foreach ($thread->series as $series)
                <span class="label label-tag"><a href="/threads/series/{{ urlencode($series->slug) }}">{{ $series->name }}</a>
                            <a href="{!! route('series.show', ['id' => $series->id]) !!}" title="Show this series."><span class='glyphicon glyphicon-link text-info'></span></a>
                        </span>
            @endforeach
        @endunless


        @unless ($thread->entities->isEmpty())
            Related:
            @foreach ($thread->entities as $entity)
                <span class="label label-tag"><a href="/threads/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a>
                            <a href="{!! route('entities.show', ['id' => $entity->id]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
                        </span>
            @endforeach
        @endunless

        @unless ($thread->tags->isEmpty())
            Tags:
            @foreach ($thread->tags as $tag)
                <span class="label label-tag"><a href="/threads/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a>
                            <a href="{!! route('tags.show', ['slug' => $tag->name]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
                        </span>
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
    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $thread->likes }}</td>
    <td class="cell-stat-2x hidden-xs">{{ $thread->lastPostAt->diffForHumans() }}</td>
</tr>
<tr>
    <td colspan="7">
        <div style="padding-left: 5px;">
            <!-- TO DO: change this to storing the trust in the user at thread save -->
            @if (isset($thread->user) && $thread->user->can('trust_thread'))
                {!! $thread->body !!}
            @else
                {{ $thread->body }}
            @endcan
        </div>
    </td>
</tr>
