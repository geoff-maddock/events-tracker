<li class="rounded-lg border border-border bg-card p-4 shadow-sm hover:shadow-md transition-shadow">

	@if ($primary = $review->event->getPrimaryPhoto())
		<div class="mb-4">
			<a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
			   data-lightbox="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
			   title="Click to see enlarged image"
			   class="block aspect-video overflow-hidden rounded-lg">
				<img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}"
					 alt="{{ $review->event->name}}"
					 class="w-full h-full object-cover hover:scale-105 transition-transform">
			</a>
		</div>
	@endif

	<div class="flex items-start gap-4">
		@if ($review->rating)
		<div class="flex-shrink-0">
			<span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/20 text-primary font-bold text-lg">
				{!! $review->rating !!}
			</span>
		</div>
		@endif

		<div class="flex-1">
			<div class='text-sm text-muted-foreground mb-2'>
				{!! $review->event->start_at->format('l F jS Y') !!}
			</div>

			<h3 class="text-lg font-semibold mb-2">
				{!! link_to_route('events.show', $review->event->name, [$review->event->id], ['class' => 'text-primary hover:underline']) !!}
				@if ($signedIn && $review->ownedBy(Auth::user()))
					<a href="/reviews/{{ $review->id }}/edit"
					   title="Edit this review"
					   class="text-muted-foreground hover:text-primary ml-2">
						<i class="bi bi-pencil text-sm"></i>
					</a>
				@endif
			</h3>

			<div class="mb-3">
				<a href="/reviews?filter[type]={{ $review->reviewType->name }}"
				   class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
					{{ $review->reviewType->name }}
				</a>
			</div>

			<div class="prose prose-sm max-w-none dark:prose-invert text-foreground">
				{!! $review->review !!}
			</div>
		</div>
	</div>

</li>
