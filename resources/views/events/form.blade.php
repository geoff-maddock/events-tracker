<div class="row">

	<div class="form-group col-md-6 mb-2">
		{!! Form::label('primary_link','Primary Link:') !!}
		{!! Form::text('primary_link', (isset($event->primary_link) ? $event->primary_link : null), ['class' => 'form-control form-background', 'autofocus' => '']) !!}
		{!! $errors->first('primary_link','<span class="help-block">:message</span>') !!}
	</div>

	@if (Config::get('app.fb_app_id') !== '999')
	<div class="form-group col-md-3 mb-1">
		<label></label>
		<button type="button" class="btn btn-info form-control" id="import-link">Import Data</button>
	</div>
	@if (isset($event->primary_link) && isset($event->id))
	<div class="form-group col-md-3 mb-1">
		<label></label>
		<a href="{!! URL::route('events.importPhoto', array('id' => $event->id)) !!}"
			class="btn btn-info form-control">Import Photo</a>
	</div>
	@endif
	@endif
</div>

<div class="row">
	<div class="form-group col-md-12 mb-1 {{$errors->has('name') ? 'has-error' : '' }}">
		{!! Form::label('name','Name') !!}
		{!! Form::text('name', (isset($event->name) ? $event->name :''), [
				'class' => 'form-control form-background', 
				'placeholder' => 'Use a clear, simple and descriptive event title']) !!}
		{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-12 mb-1 {{$errors->has('slug') ? 'has-error' : '' }}">
		{!! Form::label('slug','Slug') !!}
		{!! Form::text('slug',  (isset($event->slug) ? $event->slug :''), [
			'placeholder' => 'Unique name for this event (will validate)', 
			'class' => 'form-control form-control-sm form-background']) !!}
		{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-12 mb-1 {{$errors->has('slug') ? 'has-error' : '' }}">
		{!! Form::label('short','Short Description') !!}
		{!! Form::text('short',  (isset($event->short) ? $event->short : ''), ['class' => 'form-control form-background',
			'placeholder' => 'A concise one-sentence description of the event.'
		]) !!}
		{!! $errors->first('short','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-2">
	<div class="form-group col-md-12">
		{!! Form::label('description','Description') !!}
		{!! Form::textarea('description', (isset($event->description) ? $event->description : ''), ['class' => 'form-control form-background', 'placeholder' => 'Detailed description of the event including all relevant info not in other fields']) !!}
		{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">

	<div class="form-group col-md-3 {{$errors->has('event_type_id') ? 'has-error' : '' }}">
		{!! Form::label('event_type_id','Event type:') !!}
		{!! Form::select('event_type_id', $eventTypeOptions, (isset($event->event_type_id) ? $event->event_type_id :
		NULL),
		['class' => 'form-select form-background select2 bootstrap-5', 'data-placeholder' => '']) !!}
		{!! $errors->first('event_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('venue_id','Venue') !!}
		{!! Form::select('venue_id', $venueOptions, (isset($event->venue_id) ? $event->venue_id : NULL), 
		['class' => 'form-select select2 form-background', 'data-placeholder' => '']) !!}
		{!! $errors->first('venue_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('promoter_id','Promoter') !!}
		{!! Form::select('promoter_id', $promoterOptions, (isset($event->promoter_id) ? $event->promoter_id : NULL),
		['class' =>'form-control select2 form-background', 'data-placeholder' => '']) !!}
		{!! $errors->first('promoter_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1 collapsible collapse @if (isset($event->soundcheck_at) || isset($event->door_at)) show @else hide @endif" id="form-time">
	<div class="form-group col-md-3">
		{!! Form::label('soundcheck_at','Soundcheck At:') !!}
		{!! Form::dateTimeLocal('soundcheck_at', (isset($event->soundcheck_at) ?
		$event->soundcheck_at->format('Y-m-d\\TH:i') : NULL), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('soundcheck_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('door_at','Doors Open:') !!}
		{!! Form::dateTimeLocal('door_at', (isset($event->door_at) ?
		$event->door_at->format('Y-m-d\\TH:i') : NULL), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('door_at','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row mb-3">
	<div class="form-group col-md-3 {{$errors->has('start_at') ? 'has-error' : '' }}">
		{!! Form::label('start_at','Start At:') !!} <a href="#" class="float-end px-1"  title="Show additional time options"><i class="bi bi-clock-fill toggler" id="form-time-close-box" data-bs-target="#form-time" data-bs-toggle="collapse" aria-expanded="false" aria-controls="form-time" role="button"></i></a>
		{!! Form::dateTimeLocal('start_at', (isset($event->start_at) ? $event->start_at->format('Y-m-d\\TH:i') : NULL), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('start_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('end_at','End At:') !!}
		{!! Form::dateTimeLocal('end_at', (isset($event->end_at) ? $event->end_at->format('Y-m-d\\TH:i') : NULL), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('end_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('cancelled_at','Cancelled At:') !!}
		{!! Form::dateTimeLocal('cancelled_at', (isset($event->cancelled_at) ?	$event->cancelled_at->format('Y-m-d\\TH:i') : NULL), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('cancelled_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-1">
		
	</div>

</div>

<div class="row mb-1">
	<div class="form-group col-md-3">
		{!! Form::label('presale_price','Presale Price:') !!}
		{!! Form::text('presale_price', (isset($event->presale_price) ? $event->presale_price : ''), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('presale_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('door_price','Door Price:') !!}
		{!! Form::text('door_price', (isset($event->door_price) ? $event->door_price : ''), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('door_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('min_age','Min Age:') !!}
		{!! Form::select('min_age', [ '0' => 'All Ages', '18' => '18', '21' => '21'], (isset($event->min_age) ?
		$event->min_age : NULL), ['class' => 'form-select form-background']) !!}
		{!! $errors->first('min_age','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-3">
	<div class="form-group col-md-9 {{$errors->has('ticket_link') ? 'has-error' : '' }}">
		{!! Form::label('ticket_link','Ticket Link:') !!}
		{!! Form::text('ticket_link', (isset($event->ticket_link) ? $event->ticket_link : ''), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('ticket_link','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-3">
		{!! Form::label('series_id','Series:') !!}
		{!! Form::select('series_id', $seriesOptions, (isset($event->series_id) ? $event->series_id : NULL), 
		['class' => 'form-control select2 form-background',  'data-placeholder' => '']) !!}
		{!! $errors->first('series_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-6">
		{!! Form::label('entity_list','Related Entities:') !!}
		{!! Form::select('entity_list[]', $entityOptions, null, [
		'id' => 'entity_list',
		'class' =>'form-control select2 form-background',
		'data-placeholder' => 'Choose a related artist, producer, dj',
		'data-tags' => 'false',
		'multiple' => 'multiple']) !!}
		{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-6">
		{!! Form::label('tag_list','Tags:') !!}
		{!! Form::select('tag_list[]', $tagOptions, null, [
		'id' => 'tag_list',
		'class' =>'form-control select2 form-background',
		'data-placeholder' => 'Choose a tag',
		'data-tags' => 'true',
		'multiple' => 'multiple']) !!}
		{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-3 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
		{!! Form::label('visibility_id','Visibility:') !!}
		{!! Form::select('visibility_id', $visibilityOptions, (isset($event->visibility) ? $event->visibility->id :
		3),
		['class' => 'form-select form-background']) !!}
		{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-3">
		{!! Form::label('created_by','Owner:') !!}
		{!! Form::select('created_by', $userOptions, (isset($event->created_by) ? $event->created_by : NULL), 
		['class' =>'form-control select2 form-background', 'data-placeholder' => '']) !!}
		{!! $errors->first('created_by','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group">
	{!! Form::submit(isset($action) && $action == 'update' ? 'Update Event' : 'Add Event', ['class' =>'btn	btn-primary my-2']) !!}
</div>


@section('footer')
<script src="/js/facebook-event-custom.js"></script>
@endsection