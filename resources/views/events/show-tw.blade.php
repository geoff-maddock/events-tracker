@extends('layouts.app-tw')

@section('google.event.json')
@if (config('app.spider_blacklist') !== null && $event->venue !== null)
@if (strtolower($event->venue->name) !== strtolower(config('app.spider_blacklist')))
@include('events.google-event-json')
@endif
@else
@include('events.google-event-json')
@endif
@endsection

@section('title', $event->getDateLastTitleFormat())
@section('og-description', $event->short)
@section('description', $event->short)

@section('og-image')
@if ($photo = $event->getPrimaryPhoto()){{ Storage::disk('external')->url($photo->getStoragePath()) }}@endif
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
				<h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-4">{{ $event->name }}</h1>
							
							<div class="flex items-center gap-2">
								@if ($signedIn)
									@if ($response = $event->getEventResponse($user))
										<a href="{!! route('events.unattend', ['id' => $event->id]) !!}" class="p-2 rounded-md hover:bg-accent" title="You're attending">
											<i class="bi bi-star-fill text-primary text-xl"></i>
										</a>
									@else
										<a href="{!! route('events.attend', ['id' => $event->id]) !!}" class="p-2 rounded-md hover:bg-accent" title="Mark as attending">
											<i class="bi bi-star text-muted-foreground text-xl"></i>
										</a>
									@endif
								@endif
								
								<div class="relative inline-block text-left">
									<button type="button"
										id="event-menu-button"
										class="p-1 rounded-md hover:bg-accent text-muted-foreground hover:text-foreground transition-colors"
										title="Actions">
										<i class="bi bi-three-dots text-xl"></i>
									</button>

									<div id="event-actions-menu" class="hidden absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-card border border-border ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
										<div class="py-1" role="menu" aria-orientation="vertical">
											@if ($user && $event->user && (Auth::user()->id == $event->user->id || $user->hasGroup('super_admin')))
												<a href="{!! route('events.edit', ['event' => $event->id]) !!}" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors" role="menuitem">
													<i class="bi bi-pencil mr-2"></i>Edit Event
												</a>
												<a href="{!! route('events.duplicate', ['id' => $event->id]) !!}" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors" role="menuitem" title="Create a new event based on this event.">
													<i class="bi bi-files mr-2"></i>Duplicate Event
												</a>
												<a href="{!! route('events.createSeries', ['id' => $event->id]) !!}" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors" role="menuitem">
													<i class="bi bi-collection mr-2"></i>Create Series from Event
												</a>
												<a href="{!! route('events.instagramPost', ['id' => $event->id]) !!}" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors" role="menuitem">
													<i class="bi bi-instagram mr-2"></i>Post to Instagram
												</a>

												<div class="border-t border-border my-1"></div>
												<form action="{!! route('events.destroy', ['event' => $event->id]) !!}" method="POST" class="block">
													@csrf
													@method('DELETE')
													<button type="submit" class="delete w-full text-left px-4 py-2 text-sm text-destructive hover:bg-accent hover:text-destructive transition-colors" role="menuitem">
														<i class="bi bi-trash mr-2"></i>Delete Event
													</button>
												</form>
												<div class="border-t border-border my-1"></div>
											@endif
											<a href="{!! URL::route('events.index') !!}" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors" role="menuitem">
												<i class="bi bi-list mr-2"></i>Return to list
											</a>												<div class="border-t border-border my-1"></div>
												<button type="button" id="refresh-embeds-btn" data-slug="{{ $event->slug }}" class="w-full text-left block px-4 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors" role="menuitem">
													<i class="bi bi-arrow-repeat mr-2"></i>Refresh Embeds
												</button>										</div>
									</div>
								</div>
							</div>
						</div>

						@if ($event->visibility->name !== 'Public')
						<div class="mb-4">
							<span class="badge-tw badge-warning-tw inline-flex items-center px-3 py-1 rounded-lg text-sm">
								<i class="bi bi-exclamation-triangle mr-2"></i>
								{{ $event->visibility->name }}
								@if ($event->visibility->name == 'Cancelled' && $event->cancelled_at)
								on {{ $event->cancelled_at->format('l M jS Y') }}
								@endif
							</span>
						</div>
						@endif

						@if ($event->short)
						<p class="text-xl text-muted-foreground">{{ $event->short }}</p>
						@endif
					</div>

			<!-- Event Image -->
			@if ($photo = $event->getPrimaryPhoto())
				<div class="relative overflow-hidden rounded-lg border border-border bg-card shadow max-h-[600px] flex items-center justify-center">
				<a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="event-main" class="block w-full focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
					<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" 
						alt="{{ $event->name }} @ {{ $event->venue ? $event->venue->name : '' }}"
						class="object-contain w-full max-h-[600px] cursor-pointer hover:opacity-90 transition-opacity">
				</a>
				</div>
				@endif

						<!-- Description -->
						@if ($event->description)
						<div class="rounded-lg border bg-card shadow">
							<div class="prose max-w-none p-8 p-4 pt-2 space-y-4">
								{!! nl2br(e($event->description)) !!}
							</div>
						</div>
				@endif		
		</div>

		<!-- Sidebar -->
		<div class="lg:col-span-1 space-y-6">
			
		<!-- Event Details Card -->
		<div class="rounded-lg border border-border bg-card shadow">
						<div class="p-4 pt-2 space-y-4">
							
							<!-- Series, Type, Promoter -->
							<div class="space-y-2">
								<div class="items-center text-sm">
									@if (!empty($event->series))
									<span>
										<a href="/series/{{$event->series->slug }}" class="text-muted-foreground font-bold hover:text-foreground transition-colors">
											{!! $event->series->name !!}
										</a>
										<span class="mx-1 text-muted-foreground">series</span>
									</span>
									@endif

									<span class="text-muted-foreground font-bold">
										<a href="/events/type/{{$event->eventType->slug }}" class="hover:text-foreground">
											{{ $event->eventType->name }}
										</a>
									</span>

									@if (!empty($event->promoter_id))
									<span>
										<span class="mx-1 text-muted-foreground">by</span>
										<a href="/entities/{{$event->promoter->slug }}" class="text-muted-foreground font-bold hover:text-foreground transition-colors underline-offset-2 hover:underline">
											{!! $event->promoter->name !!}
										</a>
									</span>
									@endif

									@if ($link = $event->primary_link)
									<a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 ml-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Event link">
										<i class="bi bi-box-arrow-up-right text-muted-foreground"></i>
									</a>
									@endif
								</div>

								<!-- Date -->
								<div class="flex items-center text-sm text-muted-foreground">
									<i class="bi bi-calendar-event mr-2 h-4 w-4"></i>
									<span>{!! $event->start_at->format('l, F jS Y') !!}</span>
								@if ($event->door_at && $event->door_at->format('g:i A') !== $event->start_at->format('g:i A'))
								<span class="mx-2">•</span>
								<span>Doors {!! $event->door_at->format('g:i A') !!}</span>
								@endif
								<span class="mx-2">•</span>
									<span>Show {!! $event->start_at->format('g:i A') !!}</span>
									<a href="{!! $event->getGoogleCalendarLink() !!}" target="_blank" rel="nofollow" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Add to Google Calendar">
										<i class="bi bi-calendar-plus text-muted-foreground"></i>
									</a>
								</div>

								<!-- Venue -->
								@if (!empty($event->venue_id))
								<div class="flex items-center text-sm text-muted-foreground">
									<i class="bi bi-geo-alt mr-2 h-4 w-4"></i>
									<a href="/entities/{{$event->venue->slug }}" class="hover:text-foreground transition-colors underline-offset-2 hover:underline">
										{!! $event->venue->name !!}
									</a>
								@if ($event->venue->getPrimaryLocationMap())
								<a href="{{ $event->venue->getPrimaryLocationMap() }}" 
									target="_blank" 
									rel="noopener noreferrer" 
									class="inline-flex items-center gap-1 ml-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" 
									title="View on map">
									<i class="bi bi-map text-muted-foreground"></i>
								</a>
								@endif
							</div>
							@endif

							<!-- Age Restriction -->
							@if (isset($event->min_age))
							<div class="flex items-center text-sm text-muted-foreground">
								<i class="bi bi-person-badge mr-2 h-4 w-4"></i>
								<span>{{ $event->age_format }}</span>
							</div>
							@endif

							<!-- Price -->
							@if (isset($event->presale_price) || isset($event->door_price))
							<div class="flex items-center gap-3 text-sm">
								<i class="bi bi-cash h-4 w-4 text-muted-foreground"></i>
								@if (isset($event->presale_price))
								<span class="text-green-600 dark:text-green-500">
									Presale: ${{ floor($event->presale_price) == $event->presale_price ? number_format($event->presale_price, 0) : number_format($event->presale_price, 2) }}
								</span>
								@endif
								@if (isset($event->door_price))
								<span class="text-muted-foreground">
									Door: ${{ floor($event->door_price) == $event->door_price ? number_format($event->door_price, 0) : number_format($event->door_price, 2) }}
								</span>
								@endif
								@if ($ticket = $event->ticket_link)
								<a href="{{ $ticket }}" target="_blank" rel="noopener noreferrer" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Buy tickets">
									<i class="bi bi-ticket-perforated text-muted-foreground"></i>
								</a>
								@endif
							</div>
							@endif
						</div>

				<!-- Related Entities -->
				@unless ($event->entities->isEmpty())
				<div class="space-y-2">
					<div class="flex flex-wrap gap-2">
						@foreach ($event->entities as $entity)
							<x-entity-badge :entity="$entity" context="events" />
						@endforeach
					</div>
				</div>
				@endunless

				<!-- Tags -->
				@unless ($event->tags->isEmpty())
				<div class="flex flex-wrap gap-2">
					@foreach ($event->tags as $tag)
						<x-tag-badge :tag="$tag" context="events" />
					@endforeach
				</div>
				@endunless						</div>
					</div>

			<!-- Photo Upload -->
			@if ($user && (Auth::user()->id == $event->user?->id || $user->hasGroup('super_admin') ))
			<div class="rounded-lg border border-border bg-card shadow p-2 pt-2 space-y-4">
						<form action="/events/{{ $event->id }}/photos" class="dropzone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 text-center cursor-pointer hover:border-gray-400 dark:hover:border-gray-600 transition-colors" id="myDropzone" method="POST">
							<input type="hidden" name="_token" value="{{ csrf_token() }}">
						</form>
					</div>
					@endif

                    <!-- Photos Section -->
                    @include('partials.photo-gallery-tw', ['event' => $event, 'lightboxGroup' => 'event-gallery'])		
					
					
                    <!-- Audio Section -->
					@include('embeds.playlist-tw', ['event' => $event, 'entity' => null])

				</div>
			</div>

@stop

@section('scripts.footer')

@if ($user && (Auth::user()->id === $event->user?->id || $user->hasGroup('super_admin') ))
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
				console.log('Checking what is available:', {
					hasWindow: typeof window !== 'undefined',
					hasDropzone: typeof window.Dropzone,
					hasSwal: typeof window.Swal,
					appJsLoaded: typeof window.Visibility
				});
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
    $('button.delete').on('click', function(e){
        e.preventDefault();
        const form = $(this).parents('form');
        Swal.fire({
            title: "Are you sure?",
            text: "You will not be able to recover this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            confirmButtonText: "Yes, delete it!",
        }).then(result => {
            if (result.value) {
                form.submit();
            }
        });
    });

    // Event actions dropdown toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('event-menu-button');
        const menu = document.getElementById('event-actions-menu');

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

        // Refresh embeds button handler
        const refreshEmbedsBtn = document.getElementById('refresh-embeds-btn');
        if (refreshEmbedsBtn) {
            refreshEmbedsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const slug = this.dataset.slug;
                const btn = this;
                const originalText = btn.innerHTML;
                
                // Update button to show loading state
                btn.innerHTML = '<i class="bi bi-arrow-repeat mr-2 animate-spin"></i>Refreshing...';
                btn.disabled = true;
                
                // Close the dropdown menu
                if (menu) {
                    menu.classList.add('hidden');
                }

                if (typeof EmbedLoader !== 'undefined' && slug) {
                    // Clear the cache and reload
                    EmbedLoader.refresh('events', slug).then(function(data) {
                        // Find the playlist container and update it
                        var playlistEl = document.querySelector('.playlist-id[data-resource-type="events"]');
                        if (playlistEl && data && data.embeds && data.embeds.length > 0) {
                            var html = '<div class="space-y-4">';
                            data.embeds.forEach(function(embed) {
                                html += '<div class="rounded-md overflow-hidden">' + embed + '</div>';
                            });
                            html += '</div>';
                            playlistEl.innerHTML = html;
                        }
                        
                        // Show success message
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Embeds Refreshed',
                                text: 'The embed cache has been cleared and reloaded.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                        
                        // Restore button
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }).catch(function(error) {
                        console.error('Error refreshing embeds:', error);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to refresh embeds. Please try again.',
                                icon: 'error'
                            });
                        }
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                } else {
                    console.warn('EmbedLoader not available or slug missing');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }
    });
</script>

@stop
