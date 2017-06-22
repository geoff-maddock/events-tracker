@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	<div class="jumbotron">
	<h3>Event Repo</h3>
	<p>A guide and calander of events, weekly and  monthly series, promoters, artists, producers, djs, venues and other entities.</p>
	<P>
	<a href="{{ url('/events/all') }}" class="btn btn-info">Show all events</a>
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show paginated events</a>
	<a href="{!! URL::route('events.future') !!}" class="btn btn-info">Show future events</a>
	<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show event series</a> 
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a> 
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
	</p>

	</div>

		<div class="col-sm-1">
		<ul class="pagination pull-left" style="margin-top: 0px;">
			<li>{!! link_to_route('home', '< Past', ['day_offset' => $dayOffset-1], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
		</ul>
		</div>

		<div class="col-sm-10">
		<ul class="pagination" style="margin-top: 0px;">
		<li></li>
		</ul>
		</div>

		<div class="col-sm-1">
		<ul class="pagination pull-right" style="margin-top: 0px;">
			<li>{!! link_to_route('home', 'Future >', ['day_offset' => $dayOffset+1], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
		</ul>
		</div>

	<br style="clear: left;"/>
 
	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS --> 
	<?php $today = \Carbon\Carbon::now('America/New_York'); ?>

	<div class="row small-gutter">
		@for ($i = 0; $i < 4; $i++)  
		<?php
		 $offset = $i + $dayOffset;
		 $day = \Carbon\Carbon::parse($today)->addDay($offset);

		 ?>
			@include('events.day', ['day' => $day ])
		@endfor

	</div>
@stop