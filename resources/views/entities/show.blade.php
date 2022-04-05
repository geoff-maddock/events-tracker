@extends('app')

@section('title', $entity->getTitleFormat())

@section('og-description', $entity->short)

@section('og-image')
@if ($photo = $entity->getPrimaryPhoto()){{ URL::to('/').$photo->getStoragePath() }}@endif
@endsection

@section('content')

<h1 class="display-6 text-primary">Entity	@include('entities.crumbs', ['slug' => $entity->name])</h1>

<div id="action-menu" class="mb-2">
@if ($user && Auth::user()->id === ($entity->user ? $entity->user->id : null))
	<a href="{!! route('entities.edit', ['entity' => $entity->slug]) !!}" class="btn btn-primary"  title="Edit this entity">Edit Entity</a>
@endif
	<a href="{!! URL::route('entities.index') !!}" class="btn btn-info">Return to list</a>
</div>

<div class="row">
	<div class="col-lg-6">
		<div class="profile-card">

			@if ($photo = $entity->getPrimaryPhoto())
			<div id="event-image">
				<img src="{{ $photo->getStoragePath() }}" class="img-fluid" alt="{{ $entity->name}}">
			</div>
			@endif

			<h2 class="item-title  mt-3">{{ $entity->name }}</h2>

			@unless ($entity->aliases->isEmpty())
				<P><b>Alias(es):</b>

				@foreach ($entity->aliases as $alias)
					<span class="badge rounded-pill bg-dark"><a href="/entities/alias/{{ $alias->name }}">{{ $alias->name }}</a></span>
				@endforeach

			@endunless

			<b>{{ $entity->entityType ? $entity->entityType->name : ''}}</b><br>
			
			@if ($entity->short)
				<i>{{ $entity->short }}</i><br><br>
			@endif

			@if ($entity->description)
				<b>Description</b><br>
				<i>{{ $entity->description }}</i><br><br>
			@endif

			
				{{ count($entity->follows) }} Follows |
				@if ($follow = $entity->followedBy($user))
				<b>You Are Following</b> 
				<a href="{!! route('entities.unfollow', ['id' => $entity->id]) !!}"  title="Click to unfollow">
					<i class="bi bi-check-circle-fill text-info"></i>
				</a>
				@else
				Click to Follow 
				<a href="{!! route('entities.follow', ['id' => $entity->id]) !!}" title="Click to follow">
					<i class="bi bi-plus-circle icon"></i>
				</a>
				@endif

				@if ($user && (Auth::user()->id === $entity->user->id || $user->id === Config::get('app.superuser') ) )
				<br>
				<a href="{!! route('entities.tweet', ['id' => $entity->id]) !!}" title="Click to tweet entity">
					Tweet <i class="bi bi-twitter"></i>
				</a>
				@endif


			@unless ($entity->roles->isEmpty())
				<P>
				<b>Roles:</b>
				@foreach ($entity->roles as $role)
					<span class="badge rounded-pill bg-dark"><a href="/entities/role/{{ $role->name }}">{{ $role->name }}</a></span>
				@endforeach
			@endunless

			@unless ($entity->series->isEmpty())
				<P>
				<b>Series:</b>
				@foreach ($entity->series as $series)
						<span class="badge rounded-pill bg-dark"><a href="/series/{{ $series->id }}">{{ $series->name }}</a></span>
				@endforeach
			@endunless

			@unless ($entity->tags->isEmpty())
				<br>
				<b>Tags:</b>
				@foreach ($entity->tags as $tag)
					@include('tags.single_label')
				@endforeach
			@endunless


			@unless ($entity->locations->isEmpty())

				<P>
					<b>Location</b><br>
				@foreach ($entity->locations as $location)
				@if (isset($location->visibility) && ($location->visibility->name != 'Guarded' || ($location->visibility->name == 'Guarded' && $signedIn)))

				<span><B>{{ isset($location->locationType) ? $location->locationType->name : '' }}</B>  {{ $location->address_one }} {{ $location->neighborhood ?? '' }}  {{ $location->city }} {{ $location->state }} {{ $location->country }}

						@if (isset($location->map_url) && $location->map_url != '')
						<a href="{!! $location->map_url !!}" target="_" title="Link to map.">
							<i class="bi bi bi-geo-alt-fill"></i>
						</a>
						@endif


						@if ($signedIn && $entity->ownedBy($user))
						<a href="{!! route('entities.locations.edit', ['entity' => $entity->slug, 'location' => $location->id]) !!}" title="Edit this location.">
							<i class="bi bi-pencil"></i>
						</a>
						@endif

						@if (isset($location->capacity) && $location->capacity !== 0)
						<br>
						<b>Capacity:</b> {{  $location->capacity }}
						@endif

				</span>
				@endif
				@endforeach

			@endunless

			@if ($user && Auth::user()->id == ($entity->user ? $entity->user->id : null))
				<div class="my-2">
					<a href="{!! route('entities.locations.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Add Location</a>
				</div>
			@endif


			@unless ($entity->contacts->isEmpty())
				<P><b>Contacts</b><br>
				@foreach ($entity->contacts as $contact)
				<span><B>{{ $contact->name }}</B>  {{ $contact->email ?? '' }} {{ $contact->phone ?? '' }}
						@if ($signedIn && $entity->ownedBy($user))
						<a href="{!! route('entities.contacts.edit', ['entity' => $entity->slug, 'contact' => $contact->id]) !!}"  title="Edit this contact.">
							<i class="bi bi-pencil"></i>
						</a>
						@endif
				</span>
				<br>
				@endforeach
			@endunless

			@if ($user && Auth::user()->id == ($entity->user ? $entity->user->id : null))
				<div class="my-2"><a href="{!! route('entities.contacts.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Add Contact</a></div>
			@endif
			
			<br>
			@if ($entity->facebook_username)
					<b>Facebook:</b> <a href="https://facebook.com/{{ $entity->facebook_username }}" target="_">{{$entity->facebook_username}}</a>
			@endif

			@if ($entity->twitter_username)
					<b>Twitter:</b> <a href="https://twitter.com/{{ $entity->twitter_username }}" target="_">{{ '@' }}{{  $entity->twitter_username }}</a>
			@endif



			@unless ($entity->links->isEmpty())
					<P><b>Links</b><br>
					@foreach ($entity->links as $link)
					<span><B>{!! $link->tag !!}</B> @if ($link->is_primary === 1) <i class="bi bi-link" title="Primary link"></i></span>@endif
									@if ($signedIn && $entity->ownedBy($user))
									<a href="{!! route('entities.links.edit', ['entity' => $entity->slug, 'link' => $link->id]) !!}"  title="Edit this link.">
										<i class="bi bi-pencil"></i>
									</a>
									@endif
					</span><br>
					@endforeach
			@endunless

			@if ($user && Auth::user()->id == ($entity->user ? $entity->user->id : null))
					<div>
							<a href="{!! route('entities.links.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Add Link</a>
					</div>
			@endif

			<P>
			@if ($user && Auth::user()->id === $entity->user ? $entity->user->id : null)
				<span>
					<a href="{!! route('events.create') !!}" class="btn btn-primary">Add Event</a>
				</span>
			@endif
			</P>

			@unless ($entity->comments->isEmpty())
			<b>Comments:</b><br>
			<?php $comments = $entity->comments;?>
			@foreach ($comments as $comment)
				<div class="bg-secondary rounded-2 p-2">
					<b>{{ $comment->author->name }}</b><br>
					{!! $comment->message !!}<br>

					<small class="text-muted">{{ $comment->created_at->diffForHumans() }} </small>

					@if ($signedIn && $comment->createdBy($user))
					<a href="{!! route('entities.comments.edit', ['entity' => $entity->slug, 'comment' => $comment->id]) !!}"  title="Edit this comment.">
						<i class="bi bi-pencil"></i>
					</a>
					{!! Form::open(['route' => ['entities.comments.destroy', 'entity' => $entity->slug, 'comment' => $comment->id], 'method' => 'delete']) !!}
					<button type="submit" class="btn btn-danger btn-mini my-2">Delete</button>
					{!! Form::close() !!}
					@endif
				</div>
			@endforeach
			@endunless
			@if (Auth::user())
				<span>
					<a href="{!! route('entities.comments.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary my-2">Add Comment</a>
				</span>
			@endif
			

			<div><small class="text-muted">Added by {{ $entity->user->name ?? '' }}</small></div>

			<br>

		@if (isset($threads) && count($threads) > 0)
			@include('threads.list', ['threads' => $threads])
			{!! $threads->render() !!}
		@endif

	</div>
</div>

	<div class="col-lg-6">
		<div class="row my-2">
			@foreach ($entity->photos->chunk(4) as $set)
				@foreach ($set as $photo)
				<div class="col-2">
					<a href="{{ $photo->getStoragePath() }}" data-lightbox="grid" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $entity->name}}"  class="mw-100"></a>
					@if ($user && (Auth::user()->id == ($entity->user ? $entity->user->id : null) || $user->id == Config::get('app.superuser')))
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
				@if ($user && (Auth::user()->id == ($entity->user ? $entity->user->id : null) || $user->id == Config::get('app.superuser')))
				<form action="/entities/{{ $entity->id }}/photos" class="dropzone h-auto" id="myDropzone" method="POST">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
				</form>
				@endif
			</div>
		</div>
		<div class="row">

				<div class="col-xl-6">
					<div class="card bg-dark">

							<h5 class="card-header bg-primary">Past Events <span class="badge rounded-pill bg-dark float-end"><a href="{{ url('events/related-to/'.$entity->slug) }}">{{ $entity->pastEvents()->total() }}</a></span></h5>

							<div class="card-body">
							@include('events.list', ['events' => $entity->pastEvents()])
								{!! $entity->pastEvents()->render() !!}
							</div>


					</div>
				</div>

				<div class="col-xl-6">
					<div class="card bg-dark">

						<h5 class="card-header bg-primary">Future Events <span class="badge rounded-pill bg-dark float-end"><a href="{{ url('events/related-to/'.$entity->slug) }}">{{ $entity->futureEvents()->total() }}</a></span></h5>

							<div class="card-body">
								@include('events.list', ['events' => $entity->futureEvents()])
							</div>
						</div>
					</div>
				</div>
		</div>
	</div>
</div>
@stop

@section('scripts.footer')
<script>
window.Dropzone.autoDiscover = true;
$(document).ready(function(){

	var myDropzone = new window.Dropzone('#myDropzone', {
   		dictDefaultMessage: "Drop a file here to add an entity profile picture (Max size 5MB)"
	});

	console.log('loading dropzone code');

    $('div.dz-default.dz-message').css({'color': '#000000', 'opacity': 1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 5,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
				myDropzone.on("success", function (file) {
	                location.href = 'entities/{{ $entity->slug }}';
	                location.reload();
	            });
	            myDropzone.on("successmultiple", function (file) {
	                location.href = 'entities/{{ $entity->slug }}';
	                location.reload();
	            });
				myDropzone.on("error", function (file, message) {
					Swal.fire({
						title: "Are you sure?",
						text: "You cannot upload a file that large.",
						type: "warning",
						showCancelButton: true,
						confirmButtonColor: "#DD6B55",
						confirmButtonText: "Ok",
				}).then(result => {
	                location.href = 'entities/{{ $entity->slug }}'
	                location.reload();
					});
				});
	        },
		success: console.log('Add photos form success called')
	};

	myDropzone.options.addPhotosForm.init();

})
</script>
@stop
