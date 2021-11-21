@extends('app')

@section('title', $event->getTitleFormat())
@section('og-description', $event->short)

@section('og-image')
@if ($photo = $event->getPrimaryPhoto()){{ URL::to('/').$photo->getStoragePath() }}@endif
@endsection

@section('content')

<h1 class="display-6 text-primary">Events	@include('events.crumbs', ['slug' => $event->name])</h1>

<div id="action-menu" class="mb-2">
	@include('events.show.actions', ['event' => $event, 'user' => $user])
</div>

<div class="row">
<div class="col-lg-6">
	<div class="event-card">

		@if ($photo = $event->getPrimaryPhoto())
		<div id="event-image">
			<img src="{{ $photo->getStoragePath() }}" class="img-fluid">
		</div>
		@endif

		<div class="event-date mt-3">
			@if ($event->visibility->name !== 'Public')
				<span class="text-warning">{{ $event->visibility->name }}</span><br>
			@endif
			@if ($event->visibility->name == 'Cancelled')
				<span class="text-warning">Cancelled on {{ $event->cancelled_at->format('l F jS Y') }}</span><br>
			@endif
	
			<h3 class="listing">{!! $event->start_at->format('l F jS Y') !!}</h3>
			<h4 class="listing">{!! $event->start_at->format('g:i A') !!} {!! $event->end_time ? 'until '.$event->end_time->format('h:i A') : '' !!}</h4>
		</div>
	
		<h2>{{ $event->name }}</h2>

		<i>{{ $event->short }}</i><br>


	<b>
		@if (!empty($event->series_id))
		<a href="/series/{{$event->series_id }}">{!! $event->series->name !!}</a> series
		@endif

		<a href="/events/type/{{$event->eventType->name }}">{{ $event->eventType->name }}</a>
		<br>

		@if (!empty($event->venue_id))
			<a href="/entities/{{$event->venue->slug }}">{!! $event->venue->name !!}</a>

			@if ($event->venue->getPrimaryLocationAddress($signedIn))
				{{ $event->venue->getPrimaryLocationAddress() }}
			@endif

		@else
		no venue specified
		@endif
	</b>

	@if ($event->door_price)
	${{ number_format($event->door_price,0) }}
	@endif

 	@if ($event->min_age)
	{{ is_int($event->min_age) ? $event->min_age.'+' :  $event->min_age  }}
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
	<a href="{{ $link }}" target="_" title="Ticket link">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
			<path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
		  </svg>
	</a>
	@endif
		<a href="{!! $event->getGoogleCalendarLink() !!}" target="_" rel="nofollow" title="Add to Google Calendar">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-plus-fill" viewBox="0 0 16 16">
				<path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4V.5zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2zM8.5 8.5V10H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V11H6a.5.5 0 0 1 0-1h1.5V8.5a.5.5 0 0 1 1 0z"/>
			  </svg>
		</a>

		{{ $event->attendingCount }} users attending {{ $event->countAttended > 0 ? ', '.$event->countAttended.' user attended' : '' }}
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

		@if ($user && (Auth::user()->id === $event->user->id || $user->id === Config::get('app.superuser') ) )
			<a href="{!! route('events.tweet', ['id' => $event->id]) !!}" title="Click to tweet event">
				<i class="bi bi-twitter"></i>
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

	<br>
	@unless ($event->entities->isEmpty())
	Related Entities:
		@foreach ($event->entities as $entity)
			@include('entities.single_label')
		@endforeach
	@endunless
	<br>
	@unless ($event->tags->isEmpty())
	Tags:
		@foreach ($event->tags as $tag)
			@include('tags.single_label')
		@endforeach
	@endunless

	<div><small class="text-muted">Added by <a href="/users/{{ $event->user ? $event->user->id : ''}}">{{ $event->user ? $event->user->name : '' }}</a></small></div>

	</div>
	</div>

	<div class="col-lg-6">
		<!-- Show / hide form for adding photos; Only for users with permission -->
		<div class="row">
			@foreach ($event->photos->chunk(4) as $set)
				@foreach ($set as $photo)
					<div class="col-md-2">
					<a href="{{ $photo->getStoragePath() }}" data-lightbox="grid" title="Click to see enlarged image"  data-toggle="tooltip" data-placement="bottom">
						<img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $event->name}}"  style="max-width: 100%;">
					</a>
					@if ($user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
						@if ($signedIn || $user->id == Config::get('app.superuser'))
							{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning', $photo, 'DELETE', 'Delete the photo') !!}
							@if ($photo->is_primary)
							{!! link_form_bootstrap_icon('bi bi-star-fill text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]','','','') !!}
							@else
							{!! link_form_bootstrap_icon('bi bi-star text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo','','','') !!}
							@endif
							@if ($photo->is_event)
							{!! link_form_bootstrap_icon('bi bi-calendar2-event-fill text-primary', '/photos/'.$photo->id.'/unsetEvent', 'POST', 'Event photo [Click to unset]','','','') !!}
							@else
							{!! link_form_bootstrap_icon('bi bi-calendar2-event text-info', '/photos/'.$photo->id.'/setEvent', 'POST', 'Set as event photo','','','') !!}
							@endif
							{!! link_form_bootstrap_icon('bi bi-eye text-info', '/photos/'.$photo->id, 'GET', 'Show photo','','','') !!}

						@endif
					@endif
				</div>
				@endforeach
			@endforeach
			@if ($user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
			<div class="col">
				<form action="/events/{{ $event->id }}/photos" class="dropzone" id="myDropzone" method="POST">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
				</form>
			</div>
			@endif
		</div>
		<div class="row">
		@if (isset($thread))
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
		@endif
		</div>
	</div>
</div>
@stop

@section('scripts.footer')

@if ($user && (Auth::user()->id === $event->user->id || $user->id === Config::get('app.superuser') ) )
<script>
window.Dropzone.autoDiscover = true;
$(document).ready(function(){

	var myDropzone = new window.Dropzone('#myDropzone', {
        dictDefaultMessage: "Add a picture"
    });

    $('div.dz-default.dz-message').css({'color': '#000000', 'opacity': 1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 3,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
	            myDropzone.on("complete", function (file) {
	                location.href = 'events/{{ $event->id }}'
	                location.reload();

	            });
				console.log('dropzone init called')
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
