<li class="event-card flow-root">
	<h1>{!! link_to_route('tags.show', $tag->name, [$tag->slug], ['class' => 'item-title']) !!}
		@if ($signedIn)
			@if ($follow = $tag->followedBy($user))
			<a href="{!! route('tags.unfollow', ['id' => $tag->id]) !!}" title="You are following this tag.  Click to unfollow">
				<i class="bi bi-check-circle-fill text-info"></i>
			</a>
			@else
			<a href="{!! route('tags.follow', ['id' => $tag->id]) !!}" title="Click to follow this tag."><i class="bi bi-plus-circle text-warning"></i></a>
			@endif

            @if ($signedIn &&  Auth::user()->id == Config::get('app.superuser'))
					<a href="{!! route('tags.edit', ['tag' => $tag->id]) !!}" title="Click to edit"><i class='bi bi-pencil-fill'></i></a>
                {!! link_form_bootstrap_icon('bi bi-trash-fill text-info icon', $tag, 'DELETE', 'Delete the tag') !!}
            @endif
		@endif
	</h1>

	@if (count($tag->events) > 0)
	<span class="badge rounded-pill bg-dark">{!! link_to_route('events.tag', 'Events', [$tag->slug], ['class' => 'item-title']) !!} {{ $tag->events ? count($tag->events) : 0 }}</span>
	@endif
	@if (count($tag->series) > 0)
		<span class="badge rounded-pill bg-dark">{!! link_to_route('series.tag', 'Series', [$tag->slug], ['class' => 'item-title']) !!}  {{ $tag->series ? count($tag->series) : 0 }}</span>
	@endif
	@if (count($tag->entities) > 0)
	<span class="badge rounded-pill bg-dark">{!! link_to_route('entities.tag', 'Entities', [$tag->slug], ['class' => 'item-title']) !!} {{ $tag->entities ? count($tag->entities) : 0}}</span>
	@endif
	@if (count($tag->threads) > 0)
	<span class="badge rounded-pill bg-dark">{!! link_to_route('threads.tag', 'Threads', [$tag->slug], ['class' => 'item-title']) !!} {{ $tag->threads ? count($tag->threads) : 0 }}</span>
	@endif
	Related Tags:
	@foreach ($tagObject->relatedTags() as $key => $value)
	<span class="badge rounded-pill bg-dark">{!! link_to_route('tags.show', $key, [Str::slug(strtolower($key),'-')], ['class' => 'item-title']) !!}</span>
	@endforeach 
</li>
