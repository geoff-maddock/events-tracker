@if (count($reviews) > 0)

<ul class='space-y-4'>
	@foreach ($reviews as $review)
		@include('reviews.single-tw', ['review' => $review])
	@endforeach
</ul>

@else
	<x-empty-state compact>No reviews listed.</x-empty-state>
@endif
