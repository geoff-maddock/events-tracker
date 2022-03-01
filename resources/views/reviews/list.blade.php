@if (count($reviews) > 0)

<ul class='event-list'>
	@foreach ($reviews as $review)
				@include('reviews.single', ['review' => $review])
	@endforeach
</ul>

@else
	<ul class='review-list'><li class="flow-root"><i>No reviews listed</i></li></ul> 
@endif
