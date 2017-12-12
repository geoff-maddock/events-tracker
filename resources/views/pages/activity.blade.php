@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	
	<!-- LIST OF ALL RECENT ACTIVITY --> 
	<ul class="list-group">
	@if (count($activities) > 0)

		@foreach ($activities as $activity)  
		<li class="list-group-item {{ $activity->style }} ">
			<a href="{{ strtolower($activity->getShowLink()) }}">{{ $activity->id }}</a>

			{{ $activity->message }}<br>

			<small>by {{ $activity->userName }} {{ (isset($activity->ip_address) ? 'at '.$activity->ip_address : '') }} </small>
		 </li>
		@endforeach
		{!! $activities->render() !!}
	@endif

	</ul>
@stop