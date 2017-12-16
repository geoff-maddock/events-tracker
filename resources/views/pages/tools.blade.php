@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')
	<script src="{{ asset('/js/facebook-sdk.js') }}"></script>
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
		<li class="list-group-item ">
			<a href="events/{{ $event->id }}">{{ $event->name }}</a>
		 </li>
		@endforeach

	@endif

	</ul>
@stop

@section('footer')
	<script src="{{ asset('/js/facebook-event.js') }}"></script>
@endsection