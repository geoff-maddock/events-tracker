@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	
	<!-- LIST OF ALL RECENT ACTIVITY --> 
	<ul class="list-group">
	@if (count($activities) > 0)

		@foreach ($activities as $date => $record)
			<h4 >{{ $date }}</h4>
			@foreach ($record as $activity)
			<li class="list-group-item {{ $activity->style }} ">
				{{ $activity->id }}
				<a href="{{ strtolower($activity->getShowLink()) }}">{{ $activity->message }}</a>

				<br>

				<small>by <a href="users/{{ $activity->user_id }}">{{ $activity->userName }}</a> {{ isset($activity->created_at) ? ' on '.$activity->created_at->format('m/d/Y H:i') : '' }} {{ (isset($activity->ip_address) ? ' ['.$activity->ip_address.']' : '') }} </small>
			 </li>
			@endforeach
			@endforeach

	@endif

	</ul>
@stop