@if (isset($tag))
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('tags.show', [$tag->slug]) }}" class="text-primary hover:text-primary-hover transition-colors">
        {{ ucfirst($tag->name) }}
    </a>
    @if ($signedIn)
        @if ($follow = $tag->followedBy($user))
            <a href="{{ route('tags.unfollow', ['id' => $tag->id]) }}" title="Unfollow" class="ml-1 text-green-500 hover:text-green-400">
                <i class="bi bi-check-circle-fill"></i>
            </a>
        @else
            <a href="{{ route('tags.follow', ['id' => $tag->id]) }}" title="Follow" class="ml-1 text-gray-400 hover:text-primary">
                <i class="bi bi-plus-circle"></i>
            </a>
        @endif
    @endif
@endif

@if (isset($related))
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('entities.show', [$related->slug]) }}" class="text-primary hover:text-primary-hover transition-colors">
        {{ ucfirst($related->name) }}
    </a>
    @if ($signedIn)
        @if ($follow = $related->followedBy($user))
            <a href="{{ route('entities.unfollow', ['id' => $related->id]) }}" title="Unfollow" class="ml-1 text-green-500 hover:text-green-400">
                <i class="bi bi-check-circle-fill"></i>
            </a>
        @else
            <a href="{{ route('entities.follow', ['id' => $related->id]) }}" title="Follow" class="ml-1 text-gray-400 hover:text-primary">
                <i class="bi bi-plus-circle"></i>
            </a>
        @endif
    @endif
@endif