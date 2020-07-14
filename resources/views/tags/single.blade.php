<li class="event-card" style="clear: both;">
	<h1>{!! link_to_route('tags.show', $tag->name, [$tag->name], ['class' => 'item-title']) !!}
		@if ($signedIn)
			@if ($follow = $tag->followedBy($user))
			<a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}" title="You are following this tag.  Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
			@else
			<a href="{!! route('tags.follow', ['id' => $tag->id]) !!}" title="Click to follow this tag."><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
			@endif

            @if ($signedIn &&  Auth::user()->id == Config::get('app.superuser'))
					<a href="{!! route('tags.edit', ['tag' => $tag->id]) !!}" title="Click to edit"><span class='glyphicon glyphicon-pencil'></span></a>
                {!! link_form_icon('glyphicon-trash text-warning', $tag, 'DELETE', 'Delete the tag') !!}
            @endif
		@endif

	</h1>
	<span class="label label-tag">{!! link_to_route('events.tag', 'Events', [$tag->name], ['class' => 'item-title']) !!} @if (is_array($tag->events)){{ count($tag->events) }}@endif</span>
	<span class="label label-tag">{!! link_to_route('series.tag', 'Series', [$tag->name], ['class' => 'item-title']) !!}  @if (is_array($tag->series)){{ count($tag->series) }}@endif</span>
	<span class="label label-tag">{!! link_to_route('entities.tag', 'Entities', [$tag->name], ['class' => 'item-title']) !!}  @if (is_array($tag->entities)){{ count($tag->entities) }}@endif</span>
	<span class="label label-tag">{!! link_to_route('threads.tag', 'Threads', [$tag->name], ['class' => 'item-title']) !!} @if (is_array($tag->threads)){{ count($tag->threads) }}@endif</span>

</li>
