<tr>
    <td>{!! link_to_route('threads.show', $thread->name, [$thread->id], ['class' => 'forum-link pe-2']) !!}
        @if ($signedIn && (($thread->ownedBy($user) && $thread->isRecent()) || $user->hasGroup('super_admin')))
            <a href="{!! route('threads.edit', ['thread' => $thread->id]) !!}" title="Edit this thread." class="hover-dim"><i class="bi bi-pencil-fill icon"></i></a>
            {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $thread, 'DELETE', 'Delete the [thread]') !!}
            @if (!$thread->is_locked)
                <a href="{!! route('threads.lock', ['id' => $thread->id]) !!}" title="Lock this thread."><i class="bi bi-unlock-fill icon"></i></a>
            @else
                <a href="{!! route('threads.unlock', ['id' => $thread->id]) !!}" title="Thread is locked.  Click to unlock."><i class="bi bi-lock-fill icon"></i>
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
                            <a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
                </span>
            @endforeach
        @endunless

        @unless ($thread->tags->isEmpty())
            Tags:
            @foreach ($thread->tags as $tag)
                <span class="badge rounded-pill bg-dark"><a href="/threads/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a>
                            <a href="{!! route('tags.show', ['tag' => $tag->name]) !!}" title="Show this tag.">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link-45deg" viewBox="0 0 16 16">
                                    <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1.002 1.002 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4.018 4.018 0 0 1-.128-1.287z"/>
                                    <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243L6.586 4.672z"/>
                                  </svg>
                            </a>
                </span>
            @endforeach
        @endunless

    </td>
    <td class="cell-stat hidden-xs hidden-sm">{{ $thread->thread_category ?? 'General'}}</td>
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
