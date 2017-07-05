@extends('app')

@section('title','User Profile View')

@section('content')

	<div class="row">
	<div class="col-md-12">
	<h2>{{ $user->name }}</h2>
	<p>
	@if ($signedIn && (Auth::user()->id == $user->id || $user->id == Config::get('app.superuser') ) )	
		<a href="{!! route('users.edit', ['id' => $user->id]) !!}" class="btn btn-primary">Edit Profile</a>
	@endif
		<a href="{!! URL::route('users.index') !!}" class="btn btn-info">Return to list</a>

	</p>
	<div class="col-lg-6 profile-card">
	<b>Name </b> {{ $user->profile->first_name }} {{ $user->profile->last_name }}<br>
	<b>Alias </b> {{ $user->profile->alias }}<br>
	<b>Contact </b> <a href="mailto:{{ $user->email }}">{{ $user->email }}</a><br>
	<b>Default Theme </b> {{ $user->profile->default_theme ? $user->profile->default_theme : Config::get('app.default_theme') }}<br>

	<div class="bio">
	<b>Bio</b><br>
	<p>
		{{ $user->profile->bio or 'No bio available'}}
	</p>
	</div>

	<div class="groups">
	@unless ($user->groups->isEmpty())
		
		<P><b>Groups:</b>
		
		@foreach ($user->groups as $group)
		<span class="label label-tag"><a href="/groups/{{ $group->id }}" title="{{ $group->description }}">{{ $group->label }}</a></span>
		@endforeach

	@endunless
	</div>

	<h5>Added <b>{{ $user->created_at->format('l F jS Y') }}</b></h5>
	<br>
	</div>

	<div class="col-md-5">
		@if ($signedIn || $user->id == Config::get('app.superuser'))	
		<form action="/users/{{ $user->id }}/photos" class="dropzone" id="myDropzone" method="POST">
			<input type="hidden" name="_token" value="{{ csrf_token() }}">
		</form>
		@endif

		<br style="clear: left;"/>

		@foreach ($user->photos->chunk(4) as $set)
		<div class="row">
		@foreach ($set as $photo)
			<div class="col-md-2">
			<a href="/{{ $photo->path }}" data-lightbox="{{ $photo->path }}"><img src="/{{ $photo->thumbnail }}" alt="{{ $user->name}}"  style="max-width: 100%;"></a>
			@if ($signedIn || $user->id == Config::get('app.superuser')) 
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

	</div>	

	</div>
	<div class="row">
	<div class="col-md-12">

	<h3>Events</h3>

	<div class="col-lg-6">

		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Created <span class="label label-primary">{{ $user->eventCount }}</span></h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $user->events->take(20)])

				</div>

			</div>
		</div>
	</div>
		<div class="col-lg-6">
		<div class="bs-component">

		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Following <span class="label label-primary">{{ $user->entitiesFollowingCount }}</span></h3> 
				</div>

				<div class="panel-body">
				@include('entities.list', ['entities' => $user->getEntitiesFollowing()->take(20)])
				</div>

			</div>
		</div>

			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Attending <span class="label label-primary">{{ $user->attendingCount }}</span></h3> 
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $user->getAttending()->take(20)])
				</div>

			</div>
		</div>
	</div>

</div>
</div>

@stop

@section('scripts.footer')
<script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/dropzone.js"></script>
<script>
Dropzone.autoDiscover = false;
$(document).ready(function(){

	var myDropzone = new Dropzone('#myDropzone', {
   		dictDefaultMessage: "Drop a file here to add a user profile picture"
	});

$('div.dz-default.dz-message > span').show(); // Show message span
$('div.dz-default.dz-message').css({'opacity':1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 3,
		accept: ['.jpg','.png','.gif'],
		dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
	            myDropzone.on("complete", function (file) {
	                location.href = 'users/{{ $user->id }}'
	                location.reload();

	            });
	        }
	};

	myDropzone.options.addPhotosForm.init();
	 
})
</script>
@stop

