@if (count($reviews) > 0)

<ul class='space-y-4'>
	@foreach ($reviews as $review)
		@include('reviews.single-tw', ['review' => $review])
	@endforeach
</ul>

@else
	<div class="text-center py-8 text-muted-foreground italic">
		No reviews listed
	</div>
@endif
