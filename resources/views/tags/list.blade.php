@if (count($tags) > 0)

<ul class='event-list'>
	@foreach ($tags as $tag)

	<li class="event-card" style="clear: both;">
		<span style="font-size: 26px;">{!! link_to_route('tags.show', $tag->name, [$tag->name], ['class' => 'item-title']) !!}
			@if ($signedIn)
				@if ($follow = $tag->followedBy($user))
				<a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}" title="You are following this tag.  Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
				@else
				<a href="{!! route('tags.follow', ['id' => $tag->id]) !!}" title="Click to follow this tag."><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
				@endif

                    @if ($signedIn &&  Auth::user()->id == Config::get('app.superuser'))
						<a href="{!! route('tags.edit', ['tag' => $tag->id]) !!}" title="Click to edit"><span class='glyphicon glyphicon-pencil text-warning'></span></a>
                        {!! link_form_icon('glyphicon-trash text-warning', $tag, 'DELETE', 'Delete the tag') !!}
                    @endif
			@endif
		</span>
		<span class="label label-tag">{!! link_to_route('events.tag', 'Events', [$tag->name], ['class' => 'item-title']) !!} {{ $tag->events ? count($tag->events) : 0 }}</span>
		<span class="label label-tag">{!! link_to_route('series.tag', 'Series', [$tag->name], ['class' => 'item-title']) !!}  {{ $tag->series ? count($tag->series) : 0 }}</span>
		<span class="label label-tag">{!! link_to_route('entities.tag', 'Entities', [$tag->name], ['class' => 'item-title']) !!} {{ $tag->entities ? count($tag->entities) : 0}}</span>
		<span class="label label-tag">{!! link_to_route('threads.tag', 'Threads', [$tag->name], ['class' => 'item-title']) !!} {{ $tag->threads ? count($tag->threads) : 0 }}</span>

	</li>

	@endforeach
</ul>

@else
	<ul class='event-list'><li style='clear:both;'><i>No tags listed</i></li></ul>
@endif
