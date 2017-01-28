@extends('app')

@section('content')

@section('title','User Profile View')

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

	<div class="bio">
	<b>Bio</b><br>
	<p>
		{{ $user->profile->bio or 'No bio available'}}
	</p>
	</div>
	<h5>Added <b>{{ $user->created_at->format('l F jS Y') }}</b></h5>
	<br>
	</div>

	<div class="col-md-5">
		@if ($signedIn || $user->id == Config::get('app.superuser'))	
		<form action="/users/{{ $user->id }}/photos" class="dropzone" method="POST">
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
				{!! link_form('Delete', $photo, 'DELETE') !!}
				@if ($photo->is_primary)
				<button class="btn btn-success">Primary</button>
				{!! link_form('Unset Primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST') !!}
				@else
				{!! link_form('Make Primary', '/photos/'.$photo->id.'/setPrimary', 'POST') !!}
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
Dropzone.options.addPhotosForm = {
	maxFilesize: 3,
	accept: ['.jpg','.png','.gif']
}
</script>
@stop

