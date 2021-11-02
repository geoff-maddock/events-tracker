@extends('app')

@section('title', 'Help')

@section('content')

<h1 class="display-6 text-primary">Help</h1>

	<h4>Register / Login</h4>

	To add anything, you must first register (simple process), and then log in.

	<br>

	<h4>Entities</h4>
	<p>
	Producers, DJs, Promoters, Venues and Artists - add yourself as an <b>entity</b>.  From there, if you view your entity, you can add images, links, contacts, and link yourself to events.
	</p>
	
	<h4>Event Series</h4>
	<p>
	Add a <b>series</b> under the events menu for reoccurring events.
	</p>

	<h4>Events</h4>
	<p>
	For one offs, or instances of a series, add individual <b>events</b>.
	</p>

	<h4>Import from Facebook</h4>
	<p>
	To import (some) event data from facebook, click <i>Add Event</i> to open a form, and copy the event's FB URL into the <i>Primary Link</i> input, and click <b>Import</b>.   It will add the name, slug, short description, venue and anything else it can scrape.  Proceed as usual.
	<br><br>
	<img width='50%' src="{!! asset('images/screenshots/eventrepo_event-using-facebook-import.gif') !!}">
	</p>

	<h4>Calendar</h4>
	<p>
	Any event that has been added will appear on the calendar automatically in blue.
	For each series, the next instance that has not yet been added will be displayed on the calendar in light blue.
	</p>
@stop

@section('footer')

@stop
