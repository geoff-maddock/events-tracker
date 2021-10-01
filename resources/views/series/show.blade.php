@extends('app')

@section('title', $series->getTitleFormat())
@section('og-description', $series->short)

@section('og-image')
@if ($photo = $series->getPrimaryPhoto()){{ URL::to('/').$photo->getStoragePath() }}@endif
@endsection

@section('content')

<h1 class="display-6 text-primary">Series	@include('series.crumbs', ['slug' => $series->name])</h4>

<div id="action-menu" class="mb-2">	
	@if ($user && (Auth::user()->id == $series->user->id || $user->id == Config::get('app.superuser')  ) )
	<a href="{!! route('series.edit', ['series' => $series->id]) !!}" class="btn btn-primary">Edit Series</a>
	<a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}" class="btn btn-primary">Add Occurrence</a>
	@endif
	<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Return to list</a>
</div>

<div class="row">
	<div class="col-lg-6">
		<div class="event-card">

		@if ($photo = $series->getPrimaryPhoto())
		<div>
			<img src="{{ $photo->getStoragePath() }}" class="img-fluid">
		</div>
		@endif

		<h2 class="my-2">{{ $series->name }}</h2>

		<b>{{ $series->occurrenceType->name }}   {{ $series->occurrence_repeat }}</b>

		<p>
		Founded {!! $series->founded_at ? $series->founded_at->format('l F jS Y') : 'unknown'!!}<br>
		@if ($series->cancelled_at != NULL)
		Cancelled {!! $series->cancelled_at ? $series->cancelled_at->format('l F jS Y') : 'unknown'!!}<br>
		@endif

		@if ($series->occurrenceType->name != 'No Schedule')
		Starts {!! $series->start_at ? $series->start_at->format('g:i A') : 'unknown';  !!} - Ends {!! $series->end_at ? $series->end_at->format('h:i A') : 'unknown';  !!} ({{ $series->length() }} hours)<br>
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
			<b>{{ $series->eventType ? $series->eventType->name : ''}} {{ $series->venue ? 'at '.$series->venue->name : ' at no venue specified' }}</b>
			@if ($series->venue->getPrimaryLocationAddress() != "")
			at {{ $series->venue->getPrimaryLocationAddress() }}
			@endif 
        @endif
		</p>

		@if ($signedIn)
		<br>
		{{ count($series->followers()) }} Follows |
		@if ($follow = $series->followedBy($user))
			<b>You Are Following</b> 
			<a href="{!! route('series.unfollow', ['id' => $series->id]) !!}" title="Click to unfollow">
				<i class="bi bi-dash-circle-fill"></i>
			</a>
		@else
			Click to Follow 
			<a href="{!! route('series.follow', ['id' => $series->id]) !!}" title="Click to follow">
				<i class="bi bi-plus-circle-fill"></i>
			</a>
		@endif

		@endif

		@unless ($series->entities->isEmpty())
		<br>
		Related Entities:
			@foreach ($series->entities as $entity)
			<span class="badge rounded-pill bg-dark"><a href="/series/relatedto/{{ $entity->slug }}">{{ $entity->name }}</a>
			<a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show this entity.">
				<i class="bi bi-link-45deg"></i>
			</a>
			</span>
			@endforeach
		@endunless

		@unless ($series->tags->isEmpty())
		<br>
		Tags:
		@foreach ($series->tags as $tag)
			@include('tags.single_label')
		@endforeach
		@endunless

		<div><small class="text-muted">Added by {{ $series->user->name ?? '' }}</small></div>

	</div>
	</div>

	<div class="col-lg-6">
		<div class="row">
		@foreach ($series->photos->chunk(4) as $set)
			@foreach ($set as $photo)
			<div class="col-2">
				<a href="{{ $photo->getStoragePath() }}" data-lightbox="{{ $photo->path }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $entity->name}}"  style="max-width: 100%;"></a>
				@if ($user && (Auth::user()->id == $entity->user->id || $user->id == Config::get('app.superuser')))
					{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning', $photo, 'DELETE', 'Delete the photo') !!}
					@if ($photo->is_primary)
					{!! link_form_bootstrap_icon('bi bi-star-fill text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
					@else
					{!! link_form_bootstrap_icon('bi bi-star text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
					@endif
				@endif
			</div>
			@endforeach
		@endforeach
		<div class="col">
			@if ($user && (Auth::user()->id == $series->user->id || $user->id == Config::get('app.superuser')))
			<form action="/series/{{ $series->id }}/photos" class="dropzone h-auto" id="myDropzone" method="POST">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">
			</form>
			@endif
		</div>
</div>

<div class="row">

	<div class="col-xl-12">
		<div class="card bg-dark">

			<h5 class="card-header bg-primary">Events</h5>
			<div class="card-body">

				@include('events.list', ['events' => $events])
				{!! $events->render() !!}

			</div>
		</div>

	</div>
</div>

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
							<a href="#" ><span class='label label-tag float-end' data-toggle="tooltip" data-placement="bottom"  title="# of Threads that match this search term.">{{ count($threads)}}</span></a>
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
