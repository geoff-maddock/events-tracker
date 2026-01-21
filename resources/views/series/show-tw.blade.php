@extends('layouts.app-tw')

@section('title', $series->getTitleFormat())
@section('og-description', $series->short)

@section('og-image')
@if ($photo = $series->getPrimaryPhoto()){{ Storage::disk('external')->url($photo->getStoragePath()) }}@endif
@endsection

@section('content')

<!-- Back Button -->
<div class="mb-6">
	<a href="{{ URL::previous() }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
		<i class="bi bi-arrow-left"></i>
		<span>Back</span>
	</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">

	<!-- Main Content -->
	<div class="xl:col-span-2 space-y-6">

		<!-- Header -->
		<div>
			<div class="flex items-start justify-between">
				<h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-4">{{ $series->name }}</h1>

				<div class="flex items-center gap-2">
					@if ($signedIn)
						@if ($follow = $series->followedBy($user))
							<a href="{{ route('series.unfollow', ['id' => $series->id]) }}" class="p-2 rounded-md hover:bg-accent" title="You're following">
								<i class="bi bi-star-fill text-primary text-xl"></i>
							</a>
						@else
							<a href="{{ route('series.follow', ['id' => $series->id]) }}" class="p-2 rounded-md hover:bg-accent" title="Click to follow">
								<i class="bi bi-star text-muted-foreground text-xl"></i>
							</a>
						@endif
					@endif

					<div class="relative inline-block text-left">
						<button type="button"
							id="series-menu-button"
							class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
							title="Actions">
							<i class="bi bi-three-dots text-xl"></i>
						</button>

						<div id="series-actions-menu" class="hidden absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-dark-card border border-dark-border ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
							<div class="py-1" role="menu" aria-orientation="vertical">
								@if ($user && (Auth::user()->id == $series?->user?->id || $user->id == Config::get('app.superuser')))
									<a href="{{ route('series.edit', ['series' => $series->slug]) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-dark-surface hover:text-white transition-colors" role="menuitem">
										<i class="bi bi-pencil mr-2"></i>Edit Series
									</a>
									<a href="{{ route('series.createOccurrence', ['id' => $series->id]) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-dark-surface hover:text-white transition-colors" role="menuitem">
										<i class="bi bi-plus-circle mr-2"></i>Add Occurrence
									</a>
									<div class="border-t border-dark-border my-1"></div>
								@endif
								<a href="{{ URL::route('series.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-dark-surface hover:text-white transition-colors" role="menuitem">
									<i class="bi bi-list mr-2"></i>Return to list
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>

			@if ($series->cancelled_at != NULL)
			<div class="mb-4">
				<span class="badge-tw badge-warning-tw inline-flex items-center px-3 py-1 rounded-lg text-sm">
					<i class="bi bi-exclamation-triangle mr-2"></i>
					Cancelled on {{ $series->cancelled_at->format('l M jS Y') }}
				</span>
			</div>
			@endif

			@if ($series->short)
			<p class="text-xl text-gray-600 dark:text-gray-400">{{ $series->short }}</p>
			@endif
		</div>

		<!-- Series Image -->
		@if ($photo = $series->getPrimaryPhoto())
		<div class="aspect-video relative overflow-hidden rounded-lg border border-dark-border bg-card shadow">
			<a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="series-main" class="block w-full h-full focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
				<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
					 class="object-cover w-full h-full cursor-pointer hover:opacity-90 transition-opacity"
					 alt="{{ $series->name }}"
					 loading="lazy">
			</a>
		</div>
		@endif

		<!-- Description -->
		@if ($series->description)
		<div class="rounded-lg border bg-card shadow">
			<div class="prose max-w-none p-8 p-4 pt-2 space-y-4">
				{!! nl2br(e($series->description)) !!}
			</div>
		</div>
		@endif

		<!-- Threads Section -->
		@if ($threads)
			@php
				$thread = $threads->first()
			@endphp
			@if (isset($thread) && count($threads) > 0)
				<div class="rounded-lg border border-dark-border bg-card shadow">
					<div class="flex items-center justify-between bg-primary text-primary-foreground px-6 py-4 rounded-t-lg">
						<h5 class="text-lg font-semibold">Latest Thread</h5>
						<a href="{{ route('threads.series', ['tag' => $series->slug]) }}"
						   title="{{ count($threads) }} threads"
						   class="bg-card text-foreground px-2 py-1 rounded text-sm font-medium">
							{{ count($threads) }}
						</a>
					</div>

					<div class="p-6">
						<table class="w-full">
							@include('threads.briefFirst', ['thread' => $thread])
							@include('posts.briefList', ['thread' => $thread, 'posts' => $thread->posts])
						</table>

						<div class="mt-6 pt-6 border-t border-border">
							@if ($thread->is_locked)
								<p class="text-center text-muted-foreground">This thread has been locked.</p>
							@else
								@if ($signedIn)
									<div class="mb-3">
										<span class="text-sm text-muted-foreground">
											Add new post as <strong class="text-foreground">{{ $user->name }}</strong>
										</span>
									</div>
									<form method="POST" action="{{ $thread->path().'/posts' }}" class="space-y-3">
										@csrf
										<x-ui.textarea
											name="body"
											id="body"
											placeholder="Have something to say?"
											rows="5"
										/>
										<x-ui.button type="submit" variant="default">
											Post
										</x-ui.button>
									</form>
								@else
									<p class="text-center text-muted-foreground">
										Please <a href="{{ url('/login') }}" class="text-primary hover:underline">sign in</a> to participate in this discussion.
									</p>
								@endif
							@endif
						</div>
					</div>
				</div>
			@endif
		@endif
	</div>

	<!-- Sidebar -->
	<div class="lg:col-span-1 space-y-6">

		<!-- Series Details Card -->
		<div class="rounded-lg border border-dark-border bg-card shadow">
			<div class="p-4 pt-2 space-y-4">

				<!-- Schedule Information -->
				<div class="space-y-2">
					<div class="text-sm">
						<span class="text-gray-600 dark:text-gray-400 font-bold">
							{{ $series->occurrenceType->name }}
						</span>
						<span class="mx-1 text-gray-500">{{ $series->occurrence_repeat }}</span>
					</div>

					<div class="text-sm text-gray-500 space-y-1">
						<p>Founded {{ $series->founded_at ? $series->founded_at->format('l F jS Y') : 'unknown' }}</p>

						@if ($series->occurrenceType->name != 'No Schedule')
							<p>
								Starts {{ $series->start_at ? $series->start_at->format('g:i A') : 'unknown' }} -
								Ends {{ $series->end_at ? $series->end_at->format('h:i A') : 'unknown' }}
								({{ $series->length() }} hours)
							</p>
							@if ($nextEvent = $series->nextEvent())
								<p>
									Next: <a href="{{ route('events.show', ['event' => $nextEvent->id]) }}"
											 class="text-primary hover:underline">
										{{ $nextEvent->start_at->format('l F jS Y') }}
									</a>
								</p>
							@elseif ($series->cancelled_at == NULL)
								<p class="text-muted-foreground">
									Next: {{ $series->nextEvent() ? $series->nextEvent()->start_at->format('l F jS Y') : $series->cycleFromFoundedAt()->format('l F jS Y') }}
									(not yet created)
								</p>
							@endif
						@endif
					</div>

					<!-- Event Type -->
					@if ($series->eventType)
						<div class="text-sm">
							<a href="/events/type/{{ $series->eventType->slug }}" class="text-gray-500 dark:text-gray-400 font-bold hover:text-gray-900 dark:hover:text-gray-100">
								{{ $series->eventType->name }}
							</a>
							<span class="mx-1 text-gray-500">series</span>
						</div>
					@endif

					<!-- Promoter -->
					@if (!empty($series->promoter_id))
						<div class="flex items-center text-sm text-gray-500">
							<i class="bi bi-megaphone mr-2 h-4 w-4"></i>
							<a href="/entities/{{ $series->promoter->slug }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors underline-offset-2 hover:underline">
								{{ $series->promoter->name }}
							</a>
						</div>
					@endif

					<!-- Venue -->
					@if (!empty($series->venue_id))
						<div class="flex items-center text-sm text-gray-500">
							<i class="bi bi-geo-alt mr-2 h-4 w-4"></i>
							<a href="/entities/{{ $series->venue->slug }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors underline-offset-2 hover:underline">
								{{ $series->venue->name }}
							</a>
							@if ($series->venue->getPrimaryLocationAddress() != "")
								<span class="text-xs ml-2">{{ $series->venue->getPrimaryLocationAddress() }}</span>
							@endif
						</div>
					@endif
				</div>

				<!-- Social Links -->
				<div class="flex items-center gap-3 text-sm border-t border-border pt-4">
					@if ($link = $series->primary_link)
						<a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Primary link">
							<i class="bi bi-link-45deg text-gray-600 dark:text-gray-400"></i>
						</a>
					@endif

					@if ($ticket = $series->ticket_link)
						<a href="{{ $ticket }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Ticket link">
							<i class="bi bi-ticket-perforated text-gray-600 dark:text-gray-400"></i>
						</a>
					@endif

					@if ($series->facebook_username)
						<a href="https://facebook.com/{{ $series->facebook_username }}" target="_blank" class="inline-flex items-center gap-1 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Facebook">
							<i class="bi bi-facebook text-gray-600 dark:text-gray-400"></i>
						</a>
					@endif

					@if ($series->twitter_username)
						<a href="https://twitter.com/{{ $series->twitter_username }}" target="_blank" class="inline-flex items-center gap-1 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Twitter">
							<i class="bi bi-twitter text-gray-600 dark:text-gray-400"></i>
						</a>
					@endif

					@if ($series->instagram_username)
						<a href="https://instagram.com/{{ $series->instagram_username }}" target="_blank" class="inline-flex items-center gap-1 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Instagram">
							<i class="bi bi-instagram text-gray-600 dark:text-gray-400"></i>
						</a>
					@endif
				</div>

				<!-- Follow Info -->
				@if ($signedIn)
					<div class="text-sm text-muted-foreground border-t border-border pt-4">
						<span class="font-semibold">{{ count($series->followers()) }}</span> Follows
					</div>
				@endif

				<!-- Related Entities -->
				@unless ($series->entities->isEmpty())
				<div class="flex flex-wrap gap-2 border-t border-border pt-4">
					@foreach ($series->entities as $entity)
						<x-entity-badge :entity="$entity" context="series" />
					@endforeach
				</div>
				@endunless

				<!-- Tags -->
				@unless ($series->tags->isEmpty())
				<div class="flex flex-wrap gap-2">
					@foreach ($series->tags as $tag)
						<x-tag-badge :tag="$tag" context="series" />
					@endforeach
				</div>
				@endunless

				<!-- Creator -->
				<div class="text-xs text-muted-foreground pt-4 border-t border-border">
					Added by {{ $series?->user?->name ?? '' }}
				</div>
			</div>
		</div>

		<!-- Photo Upload -->
		@if ($user && (Auth::user()->id == $series?->user?->id || $user->hasGroup('super_admin')))
		<div class="rounded-lg border border-dark-border bg-card shadow p-2 pt-2 space-y-4">
			<form action="/series/{{ $series->id }}/photos" class="dropzone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 text-center cursor-pointer hover:border-gray-400 dark:hover:border-gray-600 transition-colors" id="myDropzone" method="POST">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">
			</form>
		</div>
		@endif

		<!-- Photos Section -->
		@include('partials.photo-gallery-tw', ['event' => $series, 'lightboxGroup' => 'series-gallery'])

		<!-- Audio Section -->
		@include('embeds.playlist', ['series' => $series])
	</div>
</div>

<!-- Events - Full Width -->
<div class="mt-6 rounded-lg border border-border bg-card shadow p-6">
	<h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
		<i class="bi bi-calendar-event"></i>
		Events
		@if (isset($events))
			<span class="text-sm font-normal text-muted-foreground ml-2">({{ $events->total() }})</span>
		@endif
	</h3>
	
	@if (isset($events) && count($events) > 0)
		<!-- Events Grid -->
		<div class="grid grid-cols-1 md:grid-cols-2 event-3col:grid-cols-3 event-4col:grid-cols-4 gap-6 mb-6">
			@foreach ($events as $event)
				@include('events.card-tw', ['event' => $event])
			@endforeach
		</div>
		
		<!-- Pagination -->
		@if ($events->hasPages())
		<div class="flex justify-center">
			{!! $events->links('vendor.pagination.tailwind') !!}
		</div>
		@endif
	@else
		<div class="text-center py-12">
			<i class="bi bi-calendar-x text-4xl text-muted-foreground/50 mb-3 block"></i>
			<p class="text-muted-foreground">No events found for this series.</p>
		</div>
	@endif
</div>

@stop


@section('scripts.footer')

@if ($user && (Auth::user()->id === $series?->user?->id || $user->hasGroup('super_admin')))
<script>
$(document).ready(function(){
	// Wait for Dropzone to be available
	var attempts = 0;
	var maxAttempts = 50; // 5 seconds max

	function initDropzone() {
		attempts++;

		if (typeof window.Dropzone === 'undefined') {
			if (attempts >= maxAttempts) {
				console.error('Dropzone failed to load after ' + (maxAttempts * 100) + 'ms');
				return;
			}
			console.log('Waiting for Dropzone to load... (attempt ' + attempts + ')');
			setTimeout(initDropzone, 100);
			return;
		}

		console.log('Dropzone loaded successfully!');
		window.Dropzone.autoDiscover = false;
		var myDropzone = new window.Dropzone('#myDropzone', {
			dictDefaultMessage: "Add a picture (Max size 5MB)"
		});

		$('div.dz-default.dz-message').css({'color': '#9ca3af', 'opacity': 1, 'background-image': 'none'});

		myDropzone.options.addPhotosForm = {
		maxFilesize: 5,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
				myDropzone.on("success", function (file) {
	                location.reload();
	            });
	            myDropzone.on("successmultiple", function (file) {
	                location.reload();
	            });
				myDropzone.on("error", function (file, message) {
					console.log(message)
					Swal.fire({
						title: "Error",
						text: "Error: "+message.message,
						icon: "error",
						confirmButtonColor: "#6366f1",
						confirmButtonText: "Ok",
				}).then(result => {
					location.reload();
					});
				});
	        },
		success: console.log('Upload successful')
	};

		myDropzone.options.addPhotosForm.init();
	}

	// Start trying to initialize Dropzone
	initDropzone();
})
</script>
@endif

<script type="text/javascript">
    // Series actions dropdown toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('series-menu-button');
        const menu = document.getElementById('series-actions-menu');

        if (menuButton && menu) {
            menuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', function(e) {
                if (!menu.contains(e.target) && !menuButton.contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });
        }
    });
</script>

@stop
