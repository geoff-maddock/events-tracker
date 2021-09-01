<tr>
    <td>
        <span class="pe-2">
        {!! link_to_route('threads.show', $thread->name, [$thread->id], ['class' => 'forum-link']) !!}
        </span>
        @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
            
        <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}" title="Edit this thread."><i class="bi bi-pencil-fill icon"></i></a>
            
            {!! link_form_bootstrap_icon('bi bi-trash-fill icon', $thread, 'DELETE', 'Delete the thread') !!}

            @if (!$thread->is_locked)
                <a href="{!! route('threads.lock', ['id' => $thread->id]) !!}" title="Lock this thread.">
                    <i class="bi bi-unlock-fill icon"></i>
                </a>
            @else
                <a href="{!! route('threads.unlock', ['id' => $thread->id]) !!}" title="Thread is locked.  Click to unlock.">
                    <i class="bi bi-lock-fill icon"></i>
                </a>
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
            <span class="badge rounded-pill bg-dark"><a href="{!! route('events.show', ['event' => $event->id]) !!}">{{ $event->name }}</a></span>
        @endif

        @unless ($thread->series->isEmpty())
            Series:
            @foreach ($thread->series as $series)
                <span class="badge rounded-pill bg-dark"><a href="/threads/series/{{ urlencode($series->slug) }}">{{ $series->name }}</a>
                            <a href="{!! route('series.show', ['series' => $series->id]) !!}" title="Show this series."><span class='glyphicon glyphicon-link text-info'></span></a>
                        </span>
            @endforeach
        @endunless


        @unless ($thread->entities->isEmpty())
            Related:
            @foreach ($thread->entities as $entity)
                <span class="badge rounded-pill bg-dark"><a href="/threads/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a>
                            <a href="{!! route('entities.show', ['entity' => $entity->id]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
                        </span>
            @endforeach
        @endunless

        @unless ($thread->tags->isEmpty())
            Tags:
            @foreach ($thread->tags as $tag)
                <span class="badge rounded-pill bg-dark"><a href="/threads/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a>
                            <a href="{!! route('tags.show', ['tag' => $tag->name]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
                        </span>
            @endforeach
        @endunless

    </td>

    <td class="cell-stat">
        @if (isset($thread->user))
            @include('users.avatar', ['user' => $thread->user])
        @else
            -
        @endif
    </td>

    <td class="cell-stat-2x hidden-xs">{{ $thread->lastPostAt->diffForHumans() }}</td>
</tr>
<tr>
    <td colspan="7">
        <div class="p-2">
            <!-- TO DO: change this to storing the trust in the user at thread save -->
            @if (isset($thread->user) && $thread->user->can('trust_thread'))
                {!! $thread->body !!}
            @else
                {{ $thread->body }}
            @endcan
        </div>
    </td>
</tr>
