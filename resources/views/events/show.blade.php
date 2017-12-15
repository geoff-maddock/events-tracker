@extends('app')

@section('title','Event View')

@section('content')

<h1>Event
	@include('events.crumbs', ['slug' => '#'.$event->id])
</h1>

<p id="show_actions">
	@include('events.show.actions', ['event' => $event, 'user' => $user])
</p>


<div class="row">
<div class="col-md-6">
	<div class="event-card">
	<div class="event-date">
        @if ($event->visibility->name == 'Cancelled')
            <span class="text-warning">Cancelled on {{ $event->cancelled_at->format('l F jS Y') }}</span><br>
        @endif
	<h3 class="listing">{!! $event->start_at->format('l F jS Y') !!}</h3>
			<h4 class="listing">{!! $event->start_at->format('h:i A') !!} {!! $event->end_time ? 'until '.$event->end_time->format('h:i A') : '' !!}</h4>
	</div>

	<h2>{{ $event->name }}</h2>

		@if ($event->getPrimaryPhoto())
		<div>
			<img src="/{{ $event->getPrimaryPhoto()->path }}" class="listing">
		</div>
		@endif

	<i>{{ $event->short }}</i><br>


	<b>
	@if (!empty($event->series_id))
	<a href="/series/{{$event->series_id }}">{!! $event->series->name !!}</a> series
	@endif

	<a href="/events/type/{{$event->eventType->name }}">{{ $event->eventType->name }}</a>
	<br>

	@if (!empty($event->venue_id))
		<a href="/entities/{{$event->venue->id }}">{!! $event->venue->name !!}</a>

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
	{{ $event->min_age }}
	@endif

	<br>
	@if ($link = $event->primary_link)
	<a href="{{ $link }}" target="_" title="Primary link">
	<span class='glyphicon glyphicon-link'></span>
	</a>
	@endif
	@if ($ticket = $event->ticket_link)
	<a href="{{ $link }}" target="_" title="Ticket link">
	<span class='glyphicon glyphicon-shopping-cart'></span>
	</a>
	@endif

	<!-- display attend - x-editable or just dropdown -->

	@if ($signedIn)
		@if ($response = $event->getEventResponse($user))
		<a href="{!! route('events.unattend', ['id' => $event->id]) !!}" title="Click to mark unattending"><span class='glyphicon glyphicon-star text-warning'></span> {{ $response->responseType->name }}</a>
		@else
		<a href="{!! route('events.attend', ['id' => $event->id]) !!}" title="Click to mark attending"><span class='glyphicon glyphicon-star text-info'></span>  No response</a>
		@endif
	@endif

	{{ $event->attendingCount }} users attending

 	<br><br>

	<p> 
	@if ($event->description)
	<event class="body">
		{!! nl2br($event->description) !!}
	</event> 
	@endif

	<br>

	<i>Added by <a href="/users/{{ $event->user->id }}">{{ $event->user->name or '' }}</a></i>

	<P>
	@unless ($event->entities->isEmpty())
	Related Entities:
		@foreach ($event->entities as $entity)
					<span class="label label-tag"><a href="/events/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a>
					<a href="{!! route('entities.show', ['id' => $entity->id]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
				</span>
		@endforeach
	@endunless
	</P>

	@unless ($event->tags->isEmpty())
	<P>Tags:
	@foreach ($event->tags as $tag)
			<span class="label label-tag"><a href="/events/tag/{{ urlencode($tag->name) }}" class="label-link">{{ $tag->name }}</a>
                        <a href="{!! route('tags.show', ['slug' => $tag->name]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
			</span>
		@endforeach
	@endunless
	</P>
	</div>
	</div>

	<div class="col-md-6">
		@if ($user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )	
		<form action="/events/{{ $event->id }}/photos" class="dropzone" id="myDropzone" method="POST">
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
		</form>
		@endif

		<br style="clear: left;"/>

		@foreach ($event->photos->chunk(4) as $set)
			<div class="row">
			@foreach ($set as $photo)
				<div class="col-md-2" style="padding-bottom: 10px;">
				<a href="/{{ $photo->path }}" data-lightbox="{{ $photo->path }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="/{{ $photo->thumbnail }}" alt="{{ $event->name}}"  style="max-width: 100%;"></a>
				@if ($user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
					@if ($signedIn || $user->id == Config::get('app.superuser'))
						{!! link_form_icon('glyphicon-trash text-warning', $photo, 'DELETE', 'Delete the photo') !!}
						@if ($photo->is_primary)
						{!! link_form_icon('glyphicon-star text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
						@else
						{!! link_form_icon('glyphicon-star-empty text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
						@endif
					@endif
				@endif
				</div>
			@endforeach
			</div>
		@endforeach
		</div>

	@if (isset($thread) && count($thread) > 0)
		<div class="col-md-6">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Posts
						<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Threads that match this search term.">{{ count($thread)}}</span></a>
					</h3>
				</div>

				<div class="panel-body">
					<table class="table forum table-striped">
					@include('threads.briefFirst', ['thread' => $thread])
					@include('posts.briefList', ['thread' => $thread, 'posts' => $thread->posts])
					</table>

					<div class="col-lg-12">

						@if ($thread->is_locked)
							<P class="text-center">This thread has been locked.</P>
						@else
							@if ($signedIn)
								Add new post as <strong>{{ $user->name }}</strong>
								<form method="POST" action="{{ $thread->path().'/posts' }}">
									{{ csrf_field() }}
									<div class="form-group">
										<textarea name="body" id="body" class="form-control" placeholder="Have something to say?" rows="5"></textarea>
									</div>
									<button type="submit" class="btn btn-default">Post</button>
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

<div class="row">
	<div class="col-md-4">
		@if ($comments = $event->comments AND count($comments) > 0)
		<b>Comments:</b><br>
		@foreach ($comments as $comment)
			<div class="well well-sm">
				<b>{{ $comment->author->name }}</b><br>
				{!! $comment->message !!}<br>
				{{ $comment->created_at->diffForHumans() }} <br>
				@if ($signedIn && $comment->createdBy($user))
				<a href="{!! route('events.comments.edit', ['event' => $event->id, 'id' => $comment->id]) !!}">
				<span class='glyphicon glyphicon-pencil'></span></a>
				{!! Form::open(['route' => ['events.comments.destroy', 'event' => $event->id, 'id' => $comment->id], 'method' => 'delete']) !!}
    			<button type="submit" class="btn btn-danger btn-mini delete">Delete</button>
				{!! Form::close() !!}

				@endif
			</div>
		@endforeach
		@endif
	</div>

</div>

<P>
@if (Auth::user())	
	<span> 
		<a href="{!! route('events.comments.create', ['id' => $event->id]) !!}" class="btn btn-primary">Add Comment</a>
	</span>
@endif
</P>

@stop

@section('scripts.footer')
<script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/dropzone.js"></script>
@if ($user && (Auth::user()->id == $event->user->id || $user->id == Config::get('app.superuser') ) )
<script>
Dropzone.autoDiscover = false;
$(document).ready(function(){

	var myDropzone = new Dropzone('#myDropzone', {
        dictDefaultMessage: "Drop a file here to add a picture"
    });

    $('div.dz-default.dz-message').css({'color': '#000000', 'opacity':1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 3,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
	            myDropzone.on("complete", function (file) {
	                location.href = 'events/{{ $event->id }}'
	                location.reload();

	            });
	        }
	};

	myDropzone.options.addPhotosForm.init();
	
})
</script>
@endif
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  swal({   
    title: "Are you sure?",
    text: "You will not be able to recover this!", 
    type: "warning",   
    showCancelButton: true,   
    confirmButtonColor: "#DD6B55",
    confirmButtonText: "Yes, delete it!", 
    closeOnConfirm: true
  }, 
   function(isConfirm){
   	if (isConfirm) {
   		form.submit();
   	};
   // 
  });
})
</script>

@stop
