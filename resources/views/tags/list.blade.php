@if (count($tags) > 0)

<ul class='event-list'>
	@foreach ($tags as $tag)

	<li class="event-card flow-root">
		<span class="fs-2">{!! link_to_route('tags.show', $tag->name, [$tag->slug], ['class' => 'item-title']) !!}</span>
			@if ($signedIn)
				@if ($follow = $tag->followedBy($user))
				<a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}" title="You are following this tag.  Click to unfollow"><i class="bi bi-check-circle-fill text-info icon"></i></a>
				@else
				<a href="{!! route('tags.follow', ['id' => $tag->id]) !!}" title="Click to follow this tag."><i class="bi bi-plus-circle text-warning  icon"></i></a>
				@endif

                    @if ($signedIn &&  Auth::user()->id == Config::get('app.superuser'))
						<a href="{!! route('tags.edit', ['tag' => $tag->slug]) !!}" title="Click to edit"><i class='bi bi-pencil-fill'></i></a>
						{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $tag, 'DELETE', 'Delete the tag', NULL, 'delete') !!} 
                    @endif
			@endif
		</span>
		@if ($tag->events_count > 0)
		<span class="badge rounded-pill bg-dark">{!! link_to_route('events.tag', 'Events', [$tag->slug], ['class' => 'item-title']) !!} {{ $tag->events_count }}</span>
		@endif
		@if ($tag->series_count > 0)
			<span class="badge rounded-pill bg-dark">{!! link_to_route('series.tag', 'Series', [$tag->slug], ['class' => 'item-title']) !!}  {{ $tag->series_count }}</span>
		@endif
		@if ($tag->entities_count > 0)
		<span class="badge rounded-pill bg-dark">{!! link_to_route('entities.tag', 'Entities', [$tag->slug], ['class' => 'item-title']) !!} {{ $tag->entities_count }}</span>
		@endif
		@if ($tag->threads_count > 0)
		<span class="badge rounded-pill bg-dark">{!! link_to_route('threads.tag', 'Threads', [$tag->slug], ['class' => 'item-title']) !!} {{ $tag->threads_count }}</span>
		@endif
	</li>

	@endforeach
</ul>

@else
	<ul class='event-list'><li class="flow-root"><i>No tags listed</i></li></ul>
@endif
