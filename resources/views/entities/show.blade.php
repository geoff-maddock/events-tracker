@extends('app')

@section('title', $entity->getTitleFormat())
@section('og-description', $entity->short)

@section('og-image')
@if ($photo = $entity->getPrimaryPhoto()){{ URL::to('/').$photo->getStoragePath() }}@endif
@endsection

@section('content')


<h4>Entity
	@include('entities.crumbs', ['slug' => $entity->slug])
</h4>

<P>
@if ($user && Auth::user()->id === $entity->user->id)
	<a href="{!! route('entities.edit', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Edit Entity</a>
@endif
	<a href="{!! URL::route('entities.index') !!}" class="btn btn-info">Return to list</a>
</P>

<div class="row">
	<div class="profile-card col-md-5">
	<h2 class='item-title'>{{ $entity->name }}</h2>

		@if ($photo = $entity->getPrimaryPhoto())
		<div>
			<img src="{{ $photo->getStoragePath()  }}" class="listing">
		</div>
		@endif

	@unless ($entity->aliases->isEmpty())

		<P><b>Aliases:</b>

		@foreach ($entity->aliases as $alias)
		<span class="label label-tag"><a href="/entities/alias/{{ $alias->name }}">{{ $alias->name }}</a></span>
		@endforeach

	@endunless
	<P>
	<b>{{ $entity->entityType ? $entity->entityType->name : ''}}</b>
	</P>
	<p>
	@if ($entity->short)
		<i>{{ $entity->short }} </i><br><br>
	@endif

	@if ($entity->description)
	<b>Description</b><br>
		<i>{{ $entity->description }} </i><br>
	@endif

	@if ($signedIn)
	<br>
	{{ count($entity->follows) }} Follows |
        @if ($follow = $entity->followedBy($user))
        <b>You Are Following</b> <a href="{!! route('entities.unfollow', ['id' => $entity->id]) !!}"  title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
        @else
        Click to Follow <a href="{!! route('entities.follow', ['id' => $entity->id]) !!}" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
        @endif

	@endif


    @unless ($entity->roles->isEmpty())
		<P><b>Roles:</b>
		@foreach ($entity->roles as $role)
		<span class="label label-tag"><a href="/entities/role/{{ $role->name }}">{{ $role->name }}</a></span>
		@endforeach

	@endunless

	@unless ($entity->series->isEmpty())

		<P><b>Series:</b>

			@foreach ($entity->series as $series)
				<span class="label label-tag"><a href="/series/{{ $series->id }}">{{ $series->name }}</a></span>
		@endforeach

	@endunless

	@unless ($entity->tags->isEmpty())

		<P><b>Tags:</b>

		@foreach ($entity->tags as $tag)
				<span class="label label-tag"><a href="/entities/tag/{{ $tag->slug }}" class="label-link">{{ $tag->name }}</a>
                        <a href="{!! route('tags.show', ['tag' => $tag->slug]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
                    </span>
		@endforeach

	@endunless
	@unless ($entity->locations->isEmpty())

		<P>
		@foreach ($entity->locations as $location)
		@if (isset($location->visibility) && ($location->visibility->name != 'Guarded' || ($location->visibility->name == 'Guarded' && $signedIn)))

		<span><B>{{ isset($location->locationType) ? $location->locationType->name : '' }}</B>  {{ $location->address_one }} {{ $location->neighborhood ?? '' }}  {{ $location->city }} {{ $location->state }} {{ $location->country }}

				@if (isset($location->map_url))
				<a href="{!! $location->map_url !!}" target="_" title="Link to map.">
				<span class='glyphicon glyphicon-map-marker'></span></a>
				@endif


				@if ($signedIn && $entity->ownedBy($user))
				<a href="{!! route('entities.locations.edit', ['entity' => $entity->slug, 'location' => $location->id]) !!}" title="Edit this location.">
				<span class='glyphicon glyphicon-pencil'></span></a>
				@endif

				<br>
				@if (isset($location->capacity))
				 <b>Capacity:</b> {{  $location->capacity }}
				@endif

		</span><br>
		@endif
		@endforeach

	@endunless

	<P>
	@if ($user && Auth::user()->id == $entity->user->id)
		<span>
			<a href="{!! route('entities.locations.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Add Location</a>
		</span>
	@endif
	</P>

 	@unless ($entity->contacts->isEmpty())
 		<P><b>Contacts:</b>
		<P>
		@foreach ($entity->contacts as $contact)
		<span><B>{{ $contact->name }}</B>  {{ $contact->email ?? '' }} {{ $contact->phone ?? '' }}
				@if ($signedIn && $entity->ownedBy($user))
				<a href="{!! route('entities.contacts.edit', ['entity' => $entity->slug, 'contact' => $contact->id]) !!}">
				<span class='glyphicon glyphicon-pencil'></span></a>
				@endif
		</span><br>
		@endforeach
	@endunless

		@if ($entity->facebook_username)
			<br>
				<b>Facebook:</b> <a href="https://facebook.com/{{ $entity->facebook_username  }}">{{$entity->facebook_username}}</a>
		@endif

		@if ($entity->twitter_username)
				<b>Twitter:</b> <a href="https://twitter.com/{{ $entity->twitter_username }}">{{ '@' }}{{  $entity->twitter_username }}</a>
		@endif

	<P>
	@if ($user && Auth::user()->id === $entity->user->id)
		<span>
			<a href="{!! route('entities.contacts.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Add Contact</a>
		</span>
	@endif
	</P>

        @unless ($entity->links->isEmpty())
                <P><b>Links:</b>
                <P>
                @foreach ($entity->links as $link)
                <span><B>{!! $link->tag !!}</B>
                                @if ($signedIn && $entity->ownedBy($user))
                                <a href="{!! route('entities.links.edit', ['entity' => $entity->slug, 'link' => $link->id]) !!}">
                                <span class='glyphicon glyphicon-pencil'></span></a>
                                @endif
                </span><br>
                @endforeach

        @endunless

        <P>
        @if ($user && Auth::user()->id === $entity->user->id)
                <span>
                        <a href="{!! route('entities.links.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Add Link</a>
                </span>
        @endif
        </P>

	<P>
	@if ($user && Auth::user()->id === $entity->user->id)
		<span>
			<a href="{!! route('events.create') !!}" class="btn btn-primary">Add Event</a>
		</span>
	@endif
	</P>

	@unless ($entity->comments->isEmpty())
	<b>Comments:</b><br>
	<?php $comments = $entity->comments;?>
	@foreach ($comments as $comment)
		<div class="well well-sm">
			<b>{{ $comment->author->name }}</b><br>
			{!! $comment->message !!}<br>
			{{ $comment->created_at->diffForHumans() }} <br>
			@if ($signedIn && $comment->createdBy($user))
			<a href="{!! route('entities.comments.edit', ['entity' => $entity->slug, 'comment' => $comment->id]) !!}">
			<span class='glyphicon glyphicon-pencil'></span></a>
			{!! Form::open(['route' => ['entities.comments.destroy', 'entity' => $entity->slug, 'comment' => $comment->id], 'method' => 'delete']) !!}
			<button type="submit" class="btn btn-danger btn-mini">Delete</button>
			{!! Form::close() !!}
			@endif
		</div>
	@endforeach
	@endunless
	<P>
	@if (Auth::user())
		<span>
			<a href="{!! route('entities.comments.create', ['entity' => $entity->slug]) !!}" class="btn btn-primary">Add Comment</a>
		</span>
	@endif
	</P>

	@if (isset($threads) && count($threads) > 0)
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Threads
						<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Threads that match this search term.">{{ count($threads)}}</span></a>
					</h3>
				</div>

				<div class="panel-body">
					@include('threads.list', ['threads' => $threads])
					{!! $threads->render() !!}
				</div>

			</div>
		</div>
	@endif

	</div>

	<div class="col-md-6">
		@if ($user && (Auth::user()->id == $entity->user->id || $user->id == Config::get('app.superuser')))
		<form action="/entities/{{ $entity->id }}/photos" class="dropzone" id="myDropzone" method="POST">
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
		</form>
		@endif

		<br style="clear: left;"/>

		@foreach ($entity->photos->chunk(4) as $set)
		<div class="row">
		@foreach ($set as $photo)
			<div class="col-md-2">

			<a href="{{ $photo->getStoragePath() }}" data-lightbox="{{ $photo->path }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $entity->name}}"  style="max-width: 100%;"></a>
			@if ($user && (Auth::user()->id == $entity->user->id || $user->id == Config::get('app.superuser')))
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

		<br>
		<div class="row">

				<div class="col-lg-6">
					<div class="bs-component">
						<div class="panel panel-info">

							<div class="panel-heading">
								<h3 class="panel-title">Past Events <span class="label label-primary">{{ count($entity->pastEvents()) }}</span></h3>
							</div>

							<div class="panel-body">
							@include('events.list', ['events' => $entity->pastEvents()])
								{!! $entity->pastEvents()->render() !!}
							</div>

						</div>
					</div>
				</div>

				<div class="col-lg-6">
					<div class="bs-component">
						<div class="panel panel-info">

							<div class="panel-heading">
								<h3 class="panel-title">Future Events <span class="label label-primary">{{ count($entity->futureEvents()) }}</span></h3>
							</div>

							<div class="panel-body">
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
window.Dropzone.autoDiscover = false;
$(document).ready(function(){

	var myDropzone = new Dropzone('#myDropzone', {
   		dictDefaultMessage: "Drop a file here to add an entity profile picture"
	});

	$('div.dz-default.dz-message > span').show(); // Show message span
	$('div.dz-default.dz-message').css({'color': '#000000', 'opacity':1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 3,
		accept: ['.jpg','.png','.gif'],
		dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
	            myDropzone.on("complete", function (file) {
	                location.href = 'entities/{{ $entity->slug }}'
	                location.reload();

	            });
	        }
	};

	myDropzone.options.addPhotosForm.init();

})
</script>
@stop
