<div id="action-menu" class="mb-2">
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info my-1">Show event index</a>
	<a href="{!! URL::route('calendar') !!}" class="btn btn-info my-1">Show calendar</a>
	@auth
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary my-1">Add an event</a>
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary my-1">Add an event series</a>
	@endauth
	@if (isset($slug) && $slug == 'Attending')
	<a href="{!! URL::route('events.export.attending') !!}" class="btn btn-primary my-1" target="_blank" aria-label="Exports your list of events in a simple, sharable plain-text format." title="Exports your list of events in a simple, sharable plain-text format.">Export</a>
    @elseif (!isset($slug))
	<a href="{!! URL::route('events.export') !!}" class="btn btn-primary my-1" target="_blank" title="Exports the list of events in a simple, sharable plain-text format.">Export TXT</a>
	@endif
	<a href="{!! URL::route('events.indexIcal') !!}" class="btn btn-primary my-1" target="_blank" title="Exports the list of events into ical format to import into other calendar apps.">Export Ical</a>
</div>
