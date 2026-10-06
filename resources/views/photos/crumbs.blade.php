@if (isset($photo))
. @include('photos.slug', ['photo' => $photo])
@endif 
@if (isset($tag))
. <a href="{{ route('tags.show', [$tag->slug]) }}" class="item-title">{{ ucfirst($tag->name) }}</a>
    @auth
        @if ($follow = $tag->followedBy($user))
        <a data-method="post" href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}" title="You are following this tag.  Click to unfollow">
            <i class="bi bi-check-circle-fill text-info icon"></i>
        </a>
        @else
        <a data-method="post" href="{!! route('tags.follow', ['id' => $tag->id]) !!}" title="Click to follow this tag."><i class="bi bi-plus-circle icon"></i></a>
        @endif
    @endauth
@endif
@if (isset($related))
. <a href="{{ route('entities.show', [$related->name]) }}" class="item-title">{{ ucfirst($related->name) }}</a>
    @if ($signedIn)
    @if ($follow = $related->followedBy($user))
    <a data-method="post" href="{!! route('entities.unfollow', ['id' => $related->id]) !!}"  title="Click to unfollow">
        <i class="bi bi-check-circle-fill text-info icon"></i>
    </a>
    @else
    <a data-method="post" href="{!! route('entities.follow', ['id' => $related->id]) !!}" title="Click to follow">
        <i class="bi bi-plus-circle icon"></i>
    </a>
    @endif

    @endif
@endif
@if (isset($type))
. {{ ucfirst($type) }}
@endif
@if (isset($slug))
. {{ ucfirst($slug) }}
@endif
@if (isset($cdate))
. {{ $cdate->toDateString() }}
@endif