@if (isset($tag) && (strtolower($tag) === strtolower($t->name)))
    <?php $match = $t;?>
	<li class='list selected' id="tag-{{ $tag->id }}"><a href="/tags/{{ $t->name }}" title="Click to show all related events and entities.">{{ $t->name }}</a>
		@if ($follow = $t->followedBy($signedIn ? $user : null))
			<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to unfollow"><i class="bi bi-dash-circle-fill text-warning"></i></a>
		@elseif ($signedIn)
			<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to follow"><i class="bi bi-plus-circle-fill text-info"></i></a>
		@else
			<a href="{!! route('login') !!}" title="Sign in to follow"><i class="bi bi-plus-circle-fill text-info"></i></a>
		@endif
	</li>
@else
	<li class='list' id="tag-{{ $tag->id }}"><a href="/tags/{{ $t->name }}">{{ $t->name }}</a>
		@if ($follow = $t->followedBy($signedIn ? $user : null))
			<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to unfollow"><i class="bi bi-dash-circle-fill text-warning"></i></a>
		@elseif ($signedIn)
			<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to follow"><i class="bi bi-plus-circle-fill text-info"></i></a>
		@else
			<a href="{!! route('login') !!}" title="Sign in to follow"><i class="bi bi-plus-circle-fill text-info"></i></a>
		@endif
	</li>
@endif