@if (isset($tag) && (strtolower($tag) === strtolower($t->name)))
    <?php $match = $t;?>
	<li class='list selected' id="tag-{{ $tag->id }}"><a href="/tags/{{ $t->name }}" title="Click to show all related events and entities.">{{ $t->name }}</a>
		@if ($signedIn)
			@if ($follow = $t->followedBy($user))
				<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
			@else
				<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
			@endif
		@endif
	</li>
@else
	<li class='list' id="tag-{{ $tag->id }}"><a href="/tags/{{ $t->name }}">{{ $t->name }}</a>
		@if ($signedIn)
			@if ($follow = $t->followedBy($user))
				<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
			@else
				<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}" class="ajax-action" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
			@endif
		@endif
	</li>
@endif