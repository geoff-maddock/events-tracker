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
@if (isset($role))
	. {{ ucfirst($role) }}
@endif
@if (isset($type))
	. {{ ucfirst($type) }}
@endif
@if (isset($slug))
	. {{ strtoupper($slug) }}
@endif 