@extends('app')

@section('content')

<h2>Event Series
	@include('series.crumbs', ['slug' => $series->slug])
</h2>
<P>
@if ($user && (Auth::user()->id == $series->user->id || $user->id == Config::get('app.superuser')  ) )
	<a href="{!! route('series.edit', ['series' => $series->id]) !!}" class="btn btn-primary">Edit Series</a>
	<a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}" class="btn btn-primary">Add Occurrence</a>
@endif
	<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Return to list</a>
</P>

<div class="row">
<div class="col-md-4">
	<div class="event-card">
	<h2>{{ $series->name }}</h2>
	@if ($photo = $series->getPrimaryPhoto())
	<div>
		<img src="{{ $photo->getStoragePath() }}" class="listing">
	</div>
	@endif

	<b>{{ $series->occurrenceType->name }}   {{ $series->occurrence_repeat }}</b>

	<p>
	Founded {!! $series->founded_at ? $series->founded_at->format('l F jS Y') : 'unknown'!!}<br>
	@if ($series->cancelled_at != NULL)
	Cancelled {!! $series->cancelled_at ? $series->cancelled_at->format('l F jS Y') : 'unknown'!!}<br>
	@endif

	@if ($series->occurrenceType->name != 'No Schedule')
	Starts {!! $series->start_at ? $series->start_at->format('h:i A') : 'unknown';  !!} - Ends {!! $series->end_at ? $series->end_at->format('h:i A') : 'unknown';  !!} ({{ $series->length() }} hours)<br>
		@if ($nextEvent = $series->nextEvent() )
			Next is {{ $nextEvent->start_at->format('l F jS Y')}}<br>
		@elseif ($series->cancelled_at == NULL)
			Next is {{ $series->nextEvent() ? $series->nextEvent()->start_at->format('l F jS Y') : $series->cycleFromFoundedAt()->format('l F jS Y') }} (not yet created)<br>
		@endif

	@endif
	</p>

	@if ($series->description)
	<description class="body">
		{!! nl2br($series->description) !!}
	</description>
	@endif

	<p>
        @if ($series->eventType)
        {{ $series->eventType ? $series->eventType->name : ''}} at {{ $series->venue ? $series->venue->name : 'No venue specified' }}</p>
        @endif

	@if ($signedIn)
	<br>
	@if ($follow = $series->followedBy($user))
	<b>You Are Following</b> <a href="{!! route('series.unfollow', ['id' => $series->id]) !!}" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
	@else
	Click to Follow <a href="{!! route('series.follow', ['id' => $series->id]) !!}" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
	@endif

	@endif

	<P>
	@unless ($series->entities->isEmpty())
	Related Entities:
		@foreach ($series->entities as $entity)
		<span class="label label-tag"><a href="/series/relatedto/{{ $entity->slug }}">{{ $entity->name }}</a>
		<a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
		</span>
		@endforeach
	@endunless
	</P>

	@unless ($series->tags->isEmpty())
	<P>Tags:
	@foreach ($series->tags as $tag)
		<span class="label label-tag"><a href="/series/tag/{{ $tag->slug }}">{{ $tag->name }}</a>
		<a href="{!! route('tags.show', ['tag' => $tag->slug]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a></span>
		@endforeach
	@endunless
	</P>

	<p>	<i>Added by {{ $series->user->name ?? '' }}</i></p>

		<div class="row">
			<div class="col-sm-12">
				<div class="bs-component">
					<div class="panel panel-info">

						<div class="panel-heading">
							<h3 class="panel-title">Events</h3>
						</div>
						<div class="panel-body">
								<div class="panel-body">
								@include('events.list', ['events' => $events])
								{!! $events->render() !!}
								</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

<div class="col-md-6">
@if ($user && (Auth::user()->id == $series->user->id || $user->id == Config::get('app.superuser') ) )
<form action="/series/{{ $series->id }}/photos" class="dropzone" id="myDropzone" method="POST">
	<input type="hidden" name="_token" value="{{ csrf_token() }}">
</form>
@endif

<br style="clear: left;"/>

@foreach ($series->photos->chunk(4) as $set)
	<div class="row">
	@foreach ($set as $photo)
		<div class="col-md-2" style="padding-bottom: 10px;">
			<a href="{{ $photo->getStoragePath() }}" data-lightbox="{{ $photo->getStoragePath() }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="{{ $photo->getStorageThumbnail() }}" alt="{{$photo->name}}"  style="max-width: 100%;" ></a>
			@if ($user && (Auth::user()->id == $series->user->id || $user->id == Config::get('app.superuser') ))
						{!! link_form_icon('glyphicon-trash text-warning', $photo, 'DELETE', 'Delete the photo') !!}
						@if ($photo->is_primary)
						{!! link_form_icon('glyphicon-star text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
						@else
						{!! link_form_icon('glyphicon-star-empty text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
						@endif
			@endif
		</div>
	@endforeach
	</div>
@endforeach
</div>
	<br>
	<div class="row">
	<!-- RELATED THREADS -->
	@if ($threads)
		@php
			$thread = $threads->first()
		@endphp
			@if (isset($thread) && count($threads) > 0)
			<div class="col-md-6">
				<div class="panel panel-info">

					<div class="panel-heading">
						<h3 class="panel-title">Posts
							<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Threads that match this search term.">{{ count($threads)}}</span></a>
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
	@endif
	</div>
</div>
@stop


@section('scripts.footer')
<script>
window.Dropzone.autoDiscover = false;
$(document).ready(function(){

    var myDropzone = new Dropzone('#myDropzone', {
        dictDefaultMessage: "Drop a file here to add a picture."
    });
    console.log('running dropzone');
    $('div.dz-default.dz-message > span').show(); // Show message span
    $('div.dz-default.dz-message').css({'color': '#000000', 'opacity':1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 3,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
	            myDropzone.on("complete", function (file) {
	                location.href = 'series/{{ $series->id }}'
	                location.reload();

	            });
	        }
	};

	myDropzone.options.addPhotosForm.init();

})
</script>
@stop
