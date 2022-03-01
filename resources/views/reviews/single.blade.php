<li class="event-card flow-root">

	@if ($primary = $review->event->getPrimaryPhoto())
		<div class="event-list-thumbnail">
			<a href="{{ $primary->getStoragePath() }}" data-lightbox="{{ $primary->getStoragePath() }}"
				title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom">
				<img src="{{ $primary->getStorageThumbnail() }}" alt="{{ $review->event->name}}"  class="thumbnail-image"></a>
		</div>
	@endif

	@if ($review->rating)
	<div class="float-start pe-3">
		<span class="fs-6">{!! $review->rating !!}</span>
	</div>
	@endif

		<div class='review-date'>{!! $review->event->start_at->format('l F jS Y') !!} </div>
	
			{!! link_to_route('events.show', $review->event->name, [$review->event->id], ['class' => 'item-title']) !!}
			@if ($signedIn && $review->ownedBy(Auth::user()))
				<a href="/reviews/{{ $review->id }}/edit" title="Edit this review."><i class="bi bi-pencil"></i></a>
			@endif

			<br>

		<a href="/reviews?filter[type]={{ $review->reviewType->name }}">{{ $review->reviewType->name }}</a>
		<br>
		<div id="review">{!! $review->review !!}</div>


</li>