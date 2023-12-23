@if (isset($tag))
. {!! link_to_route('tags.show', ucfirst($tag->name), [$tag->slug], ['class' => 'item-title']) !!}
    @if ($signedIn)
        @if ($follow = $tag->followedBy($user))
        <a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}" title="You are following this tag.  Click to unfollow">
            <i class="bi bi-check-circle-fill text-info icon"></i>
        </a>
        @else
        <a href="{!! route('tags.follow', ['id' => $tag->id]) !!}" title="Click to follow this tag."><i class="bi bi-plus-circle icon"></i></a>
        @endif
    @endif
@endif
@if (isset($related))
. {!! link_to_route('entities.show', ucfirst($related->name), [$related->slug], ['class' => 'item-title']) !!}
    @if ($signedIn)
    @if ($follow = $related->followedBy($user))
    <a href="{!! route('entities.unfollow', ['id' => $related->id]) !!}"  title="Click to unfollow">
        <i class="bi bi-check-circle-fill text-info icon"></i>
    </a>
    @else
    <a href="{!! route('entities.follow', ['id' => $related->id]) !!}" title="Click to follow">
        <i class="bi bi-plus-circle icon"></i>
    </a>
    @endif

    @endif
@endif
@if (isset($type))
. {{ ucfirst($type) }}
@endif
@if (isset($event))
. {{ $event->name }}
@endif
@if (isset($cdate))
. {{ $cdate->toDateString() }}
@endif
@if (isset($search))
. {{ ucfirst($search) }}
@endif