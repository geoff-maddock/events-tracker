@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	<!-- Add classes to show or hide based on local storage values -->
	<div class="jumbotron">

		<h3 class="font-weight-bold">{{ config('app.tagline')}} <a href="#" id="event-close-box" data-toggle="collapse" data-target="#jumbo-container"  aria-expanded="false" aria-controls="jumbo-container">...</a></h3>
		<div id="jumbo-container" class="collapse">

		<p>Arcane City is a calendar and guide to events, weekly and  monthly series, promoters, artists, producers, djs, venues and other entities.</p>
		<P>
		<a href="{!! URL::route('events.index') !!}" class="btn btn-info mt-2 mr-2">Show all events</a>
		<a href="{!! URL::route('events.future') !!}" class="btn btn-info mt-2 mr-2">Show future events</a>
		<a href="{!! URL::route('series.index') !!}" class="btn btn-info mt-2 mr-2">Show event series</a>
		<a href="{!! URL::route('events.create') !!}" class="btn btn-primary mt-2 mr-2">Add an event</a>
		<a href="{!! URL::route('series.create') !!}" class="btn btn-primary mt-2 mr-2">Add an event series</a>
		<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary mt-2 mr-2">Add an entity</a>
		@if (Auth::guest())
			<a href="{!! URL::route('register') !!}" class="btn btn-success mt-2 mr-2">Register account</a>
		@endif
		</p>
		</div>

	</div>

	<section id="4days">
        @include('events.4days')
 	</section>
@stop

@section('scripts.footer')
<script type="text/javascript">

// init app module on document load
$(function()
{
    Home.init();
});
</script>
@stop
