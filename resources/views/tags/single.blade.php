<li class="event-card" style="clear: both;">
	<h1>{!! link_to_route('tags.show', $tag->name, [$tag->name], ['class' => 'item-title']) !!}
		@if ($signedIn)
			@if ($follow = $tag->followedBy($user))
			<a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}" title="You are following this tag.  Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
			@else
			<a href="{!! route('tags.follow', ['id' => $tag->id]) !!}" title="Click to follow this tag."><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
			@endif
		@endif 
	</h1> 
	<span class="label label-tag">{!! link_to_route('events.tag', 'Events', [$tag->name], ['class' => 'item-title']) !!} {{ count($tag->events) }}</span>
	<span class="label label-tag">{!! link_to_route('series.tag', 'Series', [$tag->name], ['class' => 'item-title']) !!}  {{ count($tag->series) }}</span>
	<span class="label label-tag">{!! link_to_route('entities.tag', 'Entities', [$tag->name], ['class' => 'item-title']) !!} {{ count($tag->entities) }}</span>
	<span class="label label-tag">{!! link_to_route('threads.tag', 'Threads', [$tag->name], ['class' => 'item-title']) !!} {{ count($tag->threads) }}</span>

</li>