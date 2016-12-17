@extends('app')

@section('title','Entity View')

@section('content')


<h1>Entity
	@include('entities.crumbs', ['slug' => $entity->slug])
</h1>

<P>
@if ($user && Auth::user()->id == $entity->user->id)	
	<a href="{!! route('entities.edit', ['id' => $entity->id]) !!}" class="btn btn-primary">Edit Entity</a>
@endif
	<a href="{!! URL::route('entities.index') !!}" class="btn btn-info">Return to list</a>
</P>

<div class="row">
	<div class="col-md-4">
	<h2>{{ $entity->name }}</h2>

	<b>{{ $entity->entityType->name }}</b>
	<p>
	@if ($entity->short)
		<i>{{ $entity->short }} </i><br>
	@endif 

	@if ($entity->description)
	<b>Description</b><br>
		<i>{{ $entity->description }} </i><br>
	@endif 

	@unless ($entity->roles->isEmpty())
		
		<P><b>Roles:</b>
		
		@foreach ($entity->roles as $role)
		<span class="label label-tag"><a href="/entities/role/{{ $role->name }}">{{ $role->name }}</a></span>
		@endforeach

	@endunless

	@unless ($entity->tags->isEmpty())
		
		<P><b>Tags:</b>
		
		@foreach ($entity->tags as $tag)
		<span class="label label-tag"><a href="/entities/tag/{{ $tag->name }}">{{ $tag->name }}</a></span>
		@endforeach

	@endunless
	@unless ($entity->locations->isEmpty())
		
		<P>		
		@foreach ($entity->locations as $location)
		<span><B>{{ $location->locationType->name }}</B>  {{ $location->address_one }} {{ $location->neighborhood or '' }}  {{ $location->city }} {{ $location->state }} {{ $location->country }} / {{ $location->capacity ? 'Capacity: '.$location->capacity : ''}}
				@if (isset($location->map_url))
				<a href="{!! $location->map_url !!}" target="_" title="Link to map.">
				<span class='glyphicon glyphicon-map-marker'></span></a>
				@endif
				@if ($signedIn && $entity->ownedBy($user))
				<a href="{!! route('entities.locations.edit', ['entity' => $entity->id, 'id' => $location->id]) !!}" title="Edit this location.">
				<span class='glyphicon glyphicon-pencil'></span></a>
				@endif
		</span><br>
		@endforeach
		
	@endunless

	<P>
	@if ($user && Auth::user()->id == $entity->user->id)	
		<span> 
			<a href="{!! route('entities.locations.create', ['id' => $entity->id]) !!}" class="btn btn-primary">Add Location</a>
		</span>
	@endif
	</P>
 

 	@unless ($entity->contacts->isEmpty())
 		<P><b>Contacts:</b>
		<P>		
		@foreach ($entity->contacts as $contact)
		<span><B>{{ $contact->name }}</B>  {{ $contact->email or '' }} {{ $contact->phone or '' }}  
				@if ($signedIn && $entity->ownedBy($user))
				<a href="{!! route('entities.contacts.edit', ['entity' => $entity->id, 'id' => $contact->id]) !!}">
				<span class='glyphicon glyphicon-pencil'></span></a>
				@endif
		</span><br>
		@endforeach
		
	@endunless

	<P>
	@if ($user && Auth::user()->id == $entity->user->id)	
		<span> 
			<a href="{!! route('entities.contacts.create', ['id' => $entity->id]) !!}" class="btn btn-primary">Add Contact</a>
		</span>
	@endif
	</P>

        @unless ($entity->links->isEmpty())
                <P><b>Links:</b>
                <P>
                @foreach ($entity->links as $link)
                <span><B>{!! $link->tag !!}</B>
                                @if ($signedIn && $entity->ownedBy($user))
                                <a href="{!! route('entities.links.edit', ['entity' => $entity->id, 'id' => $link->id]) !!}">
                                <span class='glyphicon glyphicon-pencil'></span></a>
                                @endif
                </span><br>
                @endforeach

        @endunless

        <P>
        @if ($user && Auth::user()->id == $entity->user->id)
                <span>
                        <a href="{!! route('entities.links.create', ['id' => $entity->id]) !!}" class="btn btn-primary">Add Link</a>
                </span>
        @endif
        </P>

	@unless ($entity->futureEvents()->isEmpty() && $entity->pastEvents()->isEmpty())
		
	<P><b>Events:</b>
		<ul>
		@if ($events = $entity->futureEvents())
		<li><b>Next Event</b></li>
		@foreach ($events as $event)
			<li>{{ $event->start_at->format('l, M jS Y')  }} {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
		@endforeach
		@endif
		@if ($events = $entity->pastEvents())
		<li><b>Past Events</b></li>
		@foreach ($events as $event)
			<li>{{ $event->start_at->format('l, M jS Y')  }} {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
		@endforeach
		@endif
		</ul>
	</P>
	@endunless
	<P>
	@if ($user && Auth::user()->id == $entity->user->id)	
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
			<a href="{!! route('entities.comments.edit', ['entity' => $entity->id, 'id' => $comment->id]) !!}">
			<span class='glyphicon glyphicon-pencil'></span></a>
			{!! Form::open(['route' => ['events.comments.destroy', 'entity' => $entity->id, 'id' => $comment->id], 'method' => 'delete']) !!}
			<button type="submit" class="btn btn-danger btn-mini">Delete</button>
			{!! Form::close() !!}
			@endif
		</div>
	@endforeach
	@endunless
	<P>
	@if (Auth::user())	
		<span> 
			<a href="{!! route('entities.comments.create', ['id' => $entity->id]) !!}" class="btn btn-primary">Add Comment</a>
		</span>
	@endif
	</P>

	</div>

	<div class="col-md-8">
	@if ($user && Auth::user()->id == $entity->user->id)	
	<form action="/entities/{{ $entity->id }}/photos" class="dropzone" method="POST">
		<input type="hidden" name="_token" value="{{ csrf_token() }}">
	</form>
	@endif

	<br style="clear: left;"/>

	@foreach ($entity->photos->chunk(4) as $set)
	<div class="row">
	@foreach ($set as $photo)
		<div class="col-md-2">
		
		<a href="/{{ $photo->path }}" data-lightbox="{{ $photo->path }}"><img src="/{{ $photo->thumbnail }}" alt="{{ $entity->name}}"  style="max-width: 100%;"></a>
		@if ($user && Auth::user()->id == $entity->user->id)	
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
