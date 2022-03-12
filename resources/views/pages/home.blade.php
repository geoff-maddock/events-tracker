@extends('app')

@section('title', 'Event + Club Guide')

@section('content')

	<div id="home-jumbotron" class="bg-light p-5 rounded-lg m-3 primary-gradient">
		{{--https://www.grabient.com/--}}

		<h3 class="font-weight-bold">{{ config('app.tagline')}} <a href="#" class="toggler" id="event-close-box" data-bs-target="#jumboContainer" data-bs-toggle="collapse" aria-expanded="false" aria-controls="jumboContainer" role="button">...</a></h3>
		<div id="jumboContainer" class="collapsible collapse show">

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

	<section id="4days" class="container-fluid">
        @include('pages.4days')
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
