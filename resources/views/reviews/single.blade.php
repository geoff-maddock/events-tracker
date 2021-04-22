<li class="event-card" style="clear: both;">

	@if ($primary = $review->event->getPrimaryPhoto())
		<div style="float: left; padding: 5px;">
			<a href="/{{ $primary->path }}" data-lightbox="{{ $primary->path }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="/{{ $primary->thumbnail }}" alt="{{ $review->event->name}}"  ></a>
		</div>
	@endif

	@if ($review->rating)
	<div style="float: left; padding: 5px;">
		<span style="font-size: 64px;">{!! $review->rating !!}</span>
	</div>
	@endif

		<div class='review-date'>{!! $review->event->start_at->format('l F jS Y') !!} </div>
	
			{!! link_to_route('events.show', $review->event->name, [$review->event->id], ['class' => 'item-title']) !!}
			@if ($signedIn && $review->ownedBy(Auth::user()))
				<a href="/reviews/{{ $review->id }}/edit" title="Edit this review."><span class='glyphicon glyphicon-pencil'></span></a>
			@endif

			<br>

		<a href="/reviews/type/{{ urlencode($review->reviewType->name) }}">{{ $review->reviewType->name }}</a>
		<br>
		<div id="review">{!! $review->review !!}</div>


</li>