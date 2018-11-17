@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	<div class="jumbotron"
		@if ($theme == config('app.default_theme'))
			style="background-color: #FF3CAC;background-image: linear-gradient(225deg, #FF3CAC 0%, #784BA0 50%, #2B86C5 100%);"
		 @else
			style="background-color: #F4D03F;background-image: linear-gradient(132deg, #F4D03F 0%, #16A085 100%);"
		@endif
		>
		{{--https://www.grabient.com/--}}

		<h3>Event Repo <a href="#" id="event-close-box" data-toggle="visibility" data-target="#jumbo-container">...</a></h3>
		<div id="jumbo-container">

		<p>A guide and calender of events, weekly and  monthly series, promoters, artists, producers, djs, venues and other entities.</p>
		<P>
		<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show all events</a>
		<a href="{!! URL::route('events.future') !!}" class="btn btn-info">Show future events</a>
		<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show event series</a>
		<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>
		<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
		<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
		@if (Auth::guest())
			<a href="{!! URL::route('register') !!}" class="btn btn-success">Register account</a>
			@else
				<a href="{!! URL::route('register') !!}" class="btn btn-success">Register account</a>
		@endif
		</p>
		</div>

	</div>

	<section id="4days">
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