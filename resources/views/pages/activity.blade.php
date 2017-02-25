@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	
	<!-- LIST OF ALL RECENT ACTIVITY --> 
	<ul class="list-group">
	@if (count($activities) > 0)

		@foreach ($activities as $activity)  
		<li class="list-group-item {{ $activity->style }} ">
			{{ $activity->id }} {{ $activity->message }}<br>

			<small>by {{ $activity->user->name }}</small>
		 </li>
		@endforeach
	@endif

	</ul>
@stop