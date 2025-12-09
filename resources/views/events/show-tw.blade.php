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
										<a href="{!! route('events.unattend', ['id' => $event->id]) !!}" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800" title="You're attending">
											<i class="bi bi-star-fill text-yellow-500 text-xl"></i>
										</a>
									@else
										<a href="{!! route('events.attend', ['id' => $event->id]) !!}" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800" title="Mark as attending">
											<i class="bi bi-star text-gray-400 text-xl"></i>
										</a>
									@endif
								@endif
								
								@if ($user && $event->user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
								<div class="relative inline-block text-left">
									<button type="button" class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors" title="More actions">
										<i class="bi bi-three-dots text-xl"></i>
									</button>
									<!-- Dropdown menu would go here -->
								</div>
								@endif
							</div>
						</div>

						@if ($event->visibility->name !== 'Public')
						<div class="mb-4">
							<span class="inline-flex items-center px-3 py-1 bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg text-sm border border-yellow-500/30">
								<i class="bi bi-exclamation-triangle mr-2"></i>
								{{ $event->visibility->name }}
								@if ($event->visibility->name == 'Cancelled' && $event->cancelled_at)
								on {{ $event->cancelled_at->format('l M jS Y') }}
								@endif
							</span>
						</div>
						@endif

						@if ($event->short)
						<p class="text-xl text-gray-600 dark:text-gray-400">{{ $event->short }}</p>
						@endif
					</div>

			<!-- Event Image -->
			@if ($photo = $event->getPrimaryPhoto())
			<div class="aspect-video relative overflow-hidden rounded-lg border border-dark-border bg-card shadow">
						<a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="event-main" class="block w-full h-full focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
							<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" 
								 class="object-cover w-full h-full cursor-pointer hover:opacity-90 transition-opacity" 
								 alt="{{ $event->name }}"
								 loading="lazy">
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
		@endif		</div>

		<!-- Sidebar -->
		<div class="lg:col-span-1 space-y-6">
			
		<!-- Event Details Card -->
		<div class="rounded-lg border border-dark-border bg-card shadow">
						<div class="p-4 pt-2 space-y-4">
							
							<!-- Series, Type, Promoter -->
							<div class="space-y-2">
								<div class="items-center text-sm">
									@if (!empty($event->series))
									<span>
										<a href="/series/{{$event->series->slug }}" class="text-gray-600 dark:text-gray-400 font-bold hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
											{!! $event->series->name !!}
										</a>
										<span class="mx-1 text-gray-500">series</span>
									</span>
									@endif

									<span class="text-gray-500 dark:text-gray-400 font-bold">
										<a href="/events/type/{{$event->eventType->slug }}" class="hover:text-gray-900 dark:hover:text-gray-100">
											{{ $event->eventType->name }}
										</a>
									</span>

									@if (!empty($event->promoter_id))
									<span>
										<span class="mx-1 text-gray-500">by</span>
										<a href="/entities/{{$event->promoter->slug }}" class="text-gray-500 dark:text-gray-400 font-bold hover:text-gray-900 dark:hover:text-gray-100 transition-colors underline-offset-2 hover:underline">
											{!! $event->promoter->name !!}
										</a>
									</span>
									@endif

									@if ($link = $event->primary_link)
									<a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 ml-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Event link">
										<i class="bi bi-box-arrow-up-right text-gray-600 dark:text-gray-400"></i>
									</a>
									@endif
								</div>

								<!-- Date -->
								<div class="flex items-center text-sm text-gray-500">
									<i class="bi bi-calendar-event mr-2 h-4 w-4"></i>
									<span>{!! $event->start_at->format('l, F jS Y') !!}</span>
									@if ($event->door_at)
									<span class="mx-2">•</span>
									<span>Doors {!! $event->door_at->format('g:i A') !!}</span>
									@endif
									<span class="mx-2">•</span>
									<span>Show {!! $event->start_at->format('g:i A') !!}</span>
									<a href="{!! $event->getGoogleCalendarLink() !!}" target="_blank" rel="nofollow" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Add to Google Calendar">
										<i class="bi bi-calendar-plus text-gray-600 dark:text-gray-400"></i>
									</a>
								</div>

								<!-- Venue -->
								@if (!empty($event->venue_id))
								<div class="flex items-center text-sm text-gray-500">
									<i class="bi bi-geo-alt mr-2 h-4 w-4"></i>
									<a href="/entities/{{$event->venue->slug }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors underline-offset-2 hover:underline">
										{!! $event->venue->name !!}
									</a>
								</div>
								@endif

								<!-- Age Restriction -->
								@if (isset($event->min_age))
								<div class="flex items-center text-sm text-gray-500">
									<i class="bi bi-person-badge mr-2 h-4 w-4"></i>
									<span>{{ $event->age_format }}</span>
								</div>
								@endif

								<!-- Price -->
								@if (isset($event->presale_price) || isset($event->door_price))
								<div class="flex items-center gap-3 text-sm">
									<i class="bi bi-cash h-4 w-4 text-gray-500"></i>
									@if (isset($event->presale_price))
									<span class="text-green-600 dark:text-green-500">
										Presale: ${{ floor($event->presale_price) == $event->presale_price ? number_format($event->presale_price, 0) : number_format($event->presale_price, 2) }}
									</span>
									@endif
									@if (isset($event->door_price))
									<span class="text-gray-600 dark:text-gray-400">
										Door: ${{ floor($event->door_price) == $event->door_price ? number_format($event->door_price, 0) : number_format($event->door_price, 2) }}
									</span>
									@endif
									@if ($ticket = $event->ticket_link)
									<a href="{{ $ticket }}" target="_blank" rel="noopener noreferrer" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Buy tickets">
										<i class="bi bi-ticket-perforated text-gray-600 dark:text-gray-400"></i>
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
						<a href="/entities/{{ $entity->slug }}" class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
							{{ $entity->name }}
							<i class="bi bi-box-arrow-up-right ml-1 text-[10px]"></i>
						</a>
						@endforeach
					</div>
				</div>
				@endunless				<!-- Tags -->
				@unless ($event->tags->isEmpty())
				<div class="flex flex-wrap gap-2">
					@foreach ($event->tags as $tag)
					<a href="/tags/{{ $tag->name }}" class="badge-tw badge-secondary-tw text-xs hover:bg-dark-border">
						{{ $tag->name }}
					</a>
					@endforeach
				</div>
				@endunless						</div>
					</div>

			<!-- Photo Upload -->
			@if ($user && (Auth::user()->id == $event->user?->id || $user->hasGroup('super_admin') ))
			<div class="rounded-lg border border-dark-border bg-card shadow p-2 pt-2 space-y-4">
						<form action="/events/{{ $event->id }}/photos" class="dropzone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 text-center cursor-pointer hover:border-gray-400 dark:hover:border-gray-600 transition-colors" id="myDropzone" method="POST">
							<input type="hidden" name="_token" value="{{ csrf_token() }}">
							<i class="bi bi-cloud-upload text-3xl text-gray-400 mb-2"></i>
							<p class="text-sm text-gray-500 dark:text-gray-400">Add a picture (Max size 5MB)</p>
						</form>
					</div>
					@endif

                    <!-- Photos Section -->
                    @include('partials.photo-gallery-tw', ['event' => $event, 'lightboxGroup' => 'event-gallery'])					
                    <!-- Audio Section -->
					@include('embeds.playlist-tw', ['event' => $event])

				</div>
			</div>

@stop

@section('scripts.footer')

@if ($user && (Auth::user()->id === $event->user?->id || $user->hasGroup('super_admin') ))
<script>
window.Dropzone.autoDiscover = true;
$(document).ready(function(){

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
	                location.href = 'events/{{ $event->id }}';
	                location.reload();
	            });
	            myDropzone.on("successmultiple", function (file) {
	                location.href = 'events/{{ $event->id }}';
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
					location.href = 'events/{{ $event->id }}';
	                location.reload();
					});
				});
	        },
		success: console.log('Upload successful')
	};

	myDropzone.options.addPhotosForm.init();

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
</script>

@stop
