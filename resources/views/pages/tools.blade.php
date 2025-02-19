@extends('app')

@section('title', 'Tools')

@section('content')
	<h1 class="display-6 text-primary">Tools</h1>
	<div class="row">
		<div class="col-md-3">
			<label></label>
			<a href="{!! URL::route('events.importPhotos') !!}" class="btn btn-info form-control" >Import Photos</a>
		</div>
	</div>
	<br>
	<ul class="list-group">
	@if (count($events) > 0)
		@foreach ($events as $event)
		<li class="list-group-item">
			<a href="events/{{ $event->id }}">{{ $event->name }}</a>
			<a class="btn btn-default" href="events/{{ $event->id }}/import-photo">Import Photo</a>
		 </li>
		@endforeach
	@endif
	</ul>
	<div class="my-2 col-4">
		{!! Form::open(['route' => 'pages.invite']) !!}
		<input id="email" name="email" size="64" class="form-control form-background my-1">
		<div class="form-group">
			{!! Form::submit('Send Invites', ['class' =>'btn btn-primary']) !!}
		</div>
		{!! Form::close() !!}
	</div>
	<div class="my-2 col-4">
        {!! Form::open(['route' => 'users.purge']) !!}
        <div class="form-group">
            {!! Form::submit('Purge Users', ['class' =>'btn btn-primary confirm']) !!}
        </div>
        {!! Form::close() !!}
    </div>

@stop

@section('footer')
	<script src="{{ asset('/js/facebook-event.js') }}"></script>
@endsection