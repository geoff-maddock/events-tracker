@extends('app')

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

<h1 class="display-crumbs text-primary">Events	@include('events.crumbs', ['slug' => $event->name])</h1>

<div id="action-menu" class="mb-2">
	@include('events.show.actions', ['event' => $event, 'user' => $user])
</div>

<div class="row">
<div class="col-lg-6">
	<div class="event-card surface">

		@if ($photo = $event->getPrimaryPhoto())
		<div id="event-image">
			<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" class="img-fluid">
		</div>
		@endif

		<div class="event-date mt-3">
			@if ($event->visibility->name !== 'Public')
				<span class="text-warning">{{ $event->visibility->name }}
					@if ($event->visibility->name == 'Cancelled' && $event->cancelled_at)
					 on {{ $event->cancelled_at ? $event->cancelled_at->format('l M jS Y') : null }}
					@endif
				</span><br>
			@endif
	
			<h3 class="listing">{!! $event->start_at->format('l M jS Y') !!}</h3>

			<h4 class="listing">
				@if ($event->door_at)
				Doors {!! $event->door_at->format('g:i A') !!}
				@endif
				Show {!! $event->start_at->format('g:i A') !!} {!! $event->end_time ? 'until '.$event->end_time->format('h:i A') : '' !!}
			</h4>
		</div>
	
		<h2 class="item-title">{{ $event->name }}</h2>

		<i>{{ $event->short }}</i><br>


	<b>
		@if (!empty($event->series))
		<a href="/series/{{$event->series->slug }}">{!! $event->series->name !!}</a> series
		@endif

		<a href="/events/type/{{$event->eventType->slug }}">{{ $event->eventType->name }}</a>
		@if (!empty($event->promoter_id))
			by <a href="/entities/{{$event->promoter->slug }}">{!! $event->promoter->name !!}</a>				
		@endif
		<br>

		@if (!empty($event->venue_id))
			<a href="/entities/{{$event->venue->slug }}">{!! $event->venue->name !!}</a>

			@if ($event->venue->getPrimaryLocationAddress($signedIn))
				{{ $event->venue->getPrimaryLocationAddress() }}
			@endif

			@if ($event->venue)
	
			@if ($event->venue->getPrimaryLocationMap() != '')
			<a href="{!! $event->venue->getPrimaryLocationMap() !!}" target="_" title="Link to map." class="mx-1">
				<i class="bi bi-geo-alt-fill"></i>
			</a>
			@endif
	
			@endif
		@else
		no venue specified
		@endif
	</b>
	<br>
	@if (isset($event->min_age))

		@if ($event->min_age == 0) 
			All Ages
		@else 
		{{ is_int($event->min_age) ? $event->min_age.'+' :  $event->min_age  }}
		@endif
	@endif

	
	@if (isset($event->presale_price))
		@if (floor($event->presale_price) == $event->presale_price)
		<a href="{{ $event->ticket_link }}" target="_" title="Ticket link">
			${{ number_format($event->presale_price, 0) }}	
		</a>
		@else 
		<a href="{{ $event->ticket_link }}" target="_" title="Ticket link">
			${{ number_format($event->presale_price, 2) }}	
		</a>
		@endif /
	@endif

	@if (isset($event->door_price))
		@if (floor($event->door_price) == $event->door_price)
			${{ number_format($event->door_price, 0) }}
		@else 
			${{ number_format($event->door_price, 2) }}
		@endif
	@endif

	<br>
	@if ($link = $event->primary_link)
	<a href="{{ $link }}" target="_" title="Primary link">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-link-45deg" viewBox="0 0 16 16">
			<path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1.002 1.002 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4.018 4.018 0 0 1-.128-1.287z"/>
			<path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243L6.586 4.672z"/>
		  </svg>
	</a>
	@endif

	@if ($ticket = $event->ticket_link)
	<a href="{{ $ticket }}" target="_" title="Ticket link">
		<i class="bi-ticket-perforated"></i>
	</a>
	@endif

		<a href="{!! $event->getGoogleCalendarLink() !!}" target="_" rel="nofollow" title="Add to Google Calendar">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-plus-fill" viewBox="0 0 16 16">
				<path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4V.5zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2zM8.5 8.5V10H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V11H6a.5.5 0 0 1 0-1h1.5V8.5a.5.5 0 0 1 1 0z"/>
			  </svg>
		</a>
		<!-- show attending count if you are the event creator or admin -->
		@if ($signedIn && ($event->ownedBy($user) || $user->hasGroup('super_admin')))
		{{ $event->attendingCount }} users attending {{ $event->countAttended > 0 ? ', '.$event->countAttended.' user attended' : '' }}
		@endif
		<br>

	<!-- display attending actions -->

	@if ($signedIn)
		@if ($response = $event->getEventResponse($user))
			<a href="{!! route('events.unattend', ['id' => $event->id]) !!}" title="Click to mark unattending">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill text-info" viewBox="0 0 16 16">
					<path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
				  </svg> {{ $response->responseType->name }}</a>

			@if ($review = $event->getEventReview($user))
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart-fill text-warning" viewBox="0 0 16 16">
				<path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z"/>
			  </svg> Rated {!! number_format($event->avgRating,2) !!} by {{ $event->countReviews }} users
			@else
			<span>
				<a href="{!! route('events.reviews.create', ['event' => $event->id]) !!}" title="Add a review">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart text-info" viewBox="0 0 16 16">
						<path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
					  </svg>
					Add Review</a>
            </span>
            @endif
		@else
			<a href="{!! route('events.attend', ['id' => $event->id]) !!}" title="Click to mark attending">
				<i class="bi bi-star icon"></i> No response</a>
		@endif

		@if ($user && (Auth::user()->id === $event->user?->id || $user->hasGroup('super_admin') ) )
			<a href="{!! route('events.instagramPost', ['id' => $event->id]) !!}" title="Click to post to instagram">
				<i class="bi bi-instagram"></i>
			</a>
		@endif
	@endif


 	<br><br>

	<p>
	@if ($event->description)
	<event class="body">
		{!! nl2br($event->description) !!}
	</event>
	@endif

	<br><br>
	@unless ($event->entities->isEmpty())
	Related Entities:
		@foreach ($event->entities as $entity)
			@include('entities.single_label')
		@endforeach
		@php unset($entity) @endphp
	@endunless
	<br>
	@unless ($event->tags->isEmpty())
	Tags:
		@foreach ($event->tags as $tag)
			@include('tags.single_label')
		@endforeach
	@endunless

	<div><small class="text-muted">Added by <a href="/users/{{ $event->user ? $event->user?->id : ''}}">{{ $event->user ? $event->user->name : '' }}</a></small></div>

	</div>
	</div>

	<div class="col-lg-6">
		<!-- Show / hide form for adding photos; Only for users with permission -->
		<div class="row">
			@foreach ($event->photos->chunk(4) as $set)
				@foreach ($set as $photo)
					@include('photos.single', ['event' => $event, 'photo' => $photo, 'user' => $user])
				@endforeach
			@endforeach

			@foreach ($event->entities as $entity)
				@foreach ($entity->photos as $photo)
					@if ($photo->is_primary)
						@include('photos.single-no-actions', ['event' => $event, 'photo' => $photo, 'user' => $user])
					@endif
				@endforeach
			@endforeach
		
			@if ($user && (Auth::user()->id == $event->user?->id || $user->hasGroup('super_admin') ))
			<div class="col">
				<form action="/events/{{ $event->id }}/photos" class="dropzone" id="myDropzone" method="POST">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
				</form>
			</div>
			@endif
		</div>

		@include('embeds.playlist', ['event' => $event])

		@if (isset($thread))
		<div class="row">
 			<div class="col-lg-12">
				<div class="card bg-dark">

					<h5 class="card-header bg-primary">
						Posts
							<a href="#"><span class='badge rounded-pill bg-dark float-end' data-toggle="tooltip" data-placement="bottom"  title="# of posts that match this search term.">{{ count($thread->posts) }}</span></a>
					</h5>

					<div class="card-body">
						<table class="table forum table-striped">
							@include('threads.briefFirst', ['thread' => $thread])
							@include('posts.briefList', ['thread' => $thread, 'posts' => $thread->posts])
						</table>

						<div class="col-lg-12">

							@if ($thread->is_locked)
								<p class="text-center">This thread has been locked.</P>
							@else
								@if ($signedIn)
									Add new post as <strong>{{ $user->name }}</strong>
									<form method="POST" action="{{ $thread->path().'/posts' }}">
										{{ csrf_field() }}
										<div class="form-group">
											<textarea name="body" id="body" class="form-control form-background" placeholder="Have something to say?" rows="5"></textarea>
										</div>
										<button type="submit" class="btn btn-primary my-2">Post</button>
									</form>

								@else
									<p class="text-center">Please <a href="{{ url('/login')}}">sign in</a> to participate in this discussion.</p>
								@endif
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
		@endif
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

    $('div.dz-default.dz-message').css({'color': '#000000', 'opacity': 1, 'background-image': 'none'});

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
						title: "Are you sure?",
						text: "Error: "+message.message,
						type: "warning",
						showCancelButton: true,
						confirmButtonColor: "#DD6B55",
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
        console.log('show blade button.delete');
        const form = $(this).parents('form');
        Swal.fire({
            title: "Are you sure? show",
            text: "You will not be able to recover this!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            preConfirm: function() {
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        resolve()
                    }, 2000)
                })
            }
        }).then(result => {
            if (result.value) {
                // handle Confirm button click
                // result.value will contain `true` or the input value
                form.submit();
            } else {
                // handle dismissals
                // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                console.log('Cancelled confirm')
            }
        });
    });

		// handles the filter js
		$(document).ready(function() {
		$('#filters').click(function() {
			
			if ($('#photo-control-toggle').hasClass('filter-closed')) {
				console.log('toggle open');
				$('#photo-control-toggle').removeClass('filter-closed');
				$('#photo-control-toggle').addClass('filter-open');
				$('#photo-control-toggle').html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-up" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z"/></svg>');
				$('#photo-control').removeClass('d-none');
				
			} else {
				console.log('toggle closed');
				$('#photo-control-toggle').removeClass('filter-open');
				$('#photo-control-toggle').addClass('filter-closed');
				$('#photo-control-toggle').html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>');
				$('#photo-control').addClass('d-none');
			}
		});
	});

</script>

@stop
