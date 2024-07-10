<div class="row mb-1">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }}">
		{!! Form::label('name','Name') !!}
		{!! Form::text('name', null, ['class' => 'form-control form-background', 'placeholder' => 'Use a clear, simple and descriptive series title', 'autofocus' => '']) !!}
		{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>	

<div class="row mb-1">
	<div class="form-group {{$errors->has('slug') ? 'has-error' : '' }}">
		{!! Form::label('slug','Slug') !!}
		{!! Form::text('slug', null, ['placeholder' => 'Unique name for this series (will validate)', 
		'class'	=> 'form-control form-background form-control-sm']) !!}
		{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">

	<div class="form-group {{$errors->has('short') ? 'has-error' : '' }}">
		{!! Form::label('short','Short Description') !!}
		{!! Form::text('short', null, ['class' => 'form-control form-background', 'placeholder' => 'A concise one-sentence description of the series.']) !!}
		{!! $errors->first('short','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-2">
	<div class="form-group {{$errors->has('description') ? 'has-error' : '' }}">
		{!! Form::label('description','Description') !!}
		{!! Form::textarea('description', null, ['class' => 'form-control form-background', 'placeholder' => 'Detailed description of the series including all relevant info not in other fields']) !!}
		{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row  mb-1">

	<div class="form-group col-md-2">
		{!! Form::label('founded_at','Founded At:') !!}
		{!! Form::dateTimeLocal('founded_at', (isset($series->founded_at)) ? $series->founded_at->format('Y-m-d\\TH:i')
		: NULL, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('founded_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
		{!! Form::label('cancelled_at','Cancelled At:') !!}
		{!! Form::dateTimeLocal('cancelled_at', (isset($series->cancelled_at)) ?
		$series->cancelled_at->format('Y-m-d\\TH:i') : NULL, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('cancelled_at','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row  mb-1">

	<div class="form-group col-md-2 {{$errors->has('occurrence_type_id') ? 'has-error' : '' }}">
		{!! Form::label('occurrence_type_id','Occurrence type:') !!}
		{!! Form::select('occurrence_type_id', $occurrenceTypeOptions, (isset($series->occurrence_type_id) ?
		$series->occurrence_type : NULL), ['class' => 'form-select form-background', 'data-placeholder' => '']) !!}
		{!! $errors->first('occurrence_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2 {{$errors->has('occurrence_week_id') ? 'has-error' : '' }}">
		{!! Form::label('occurrence_week_id','Occurrence Week') !!}
		{!! Form::select('occurrence_week_id', $weekOptions, (isset($series->occurrence_week_id) ?
		$series->occurrence_week : NULL), ['class' => 'form-select form-background', 'data-placeholder' => '']) !!}
		{!! $errors->first('occurrence_week_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2  {{$errors->has('occurrence_day_id') ? 'has-error' : '' }}">
		{!! Form::label('occurrence_day_id','Occurrence Day') !!}
		{!! Form::select('occurrence_day_id', $dayOptions, (isset($series->occurrence_day_id) ? $series->occurrence_day
		: NULL), ['class' => 'form-select form-background']) !!}
		{!! $errors->first('occurrence_day_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2  {{$errors->has('hold_date') ? 'has-error' : '' }}">
		{!! Form::label('hold_date','Hold date') !!}
		{!! Form::checkbox('hold_date', (isset($series->hold_date) ? $series->hold_date : NULL)) !!}
		{!! $errors->first('hold_date','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row  mb-2">

	<div class="form-group col-md-2 {{$errors->has('event_type_id') ? 'has-error' : '' }}">
		{!! Form::label('event_type_id','Event type:') !!}
		{!! Form::select('event_type_id', $eventTypeOptions, (isset($series->event_type_id) ? $series->event_type_id : NULL), ['class' => 'form-select form-background select2', 'data-placeholder' => '']) !!}
		{!! $errors->first('event_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2  {{$errors->has('venue_id') ? 'has-error' : '' }}">
		{!! Form::label('venue_id','Venue') !!}
		{!! Form::select('venue_id', $venueOptions, (isset($series->venue_id) ? $series->venue_id : NULL), 
		['class' => 'form-select select2', 'data-placeholder' => '']) !!}
		{!! $errors->first('venue_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2  {{$errors->has('promoter_id') ? 'has-error' : '' }}">
		{!! Form::label('promoter_id','Promoter') !!}
		{!! Form::select('promoter_id', $promoterOptions, (isset($series->promoter_id) ? $series->promoter_id : NULL),
		['class' =>'form-select select2', 'data-placeholder' => '']) !!}
		{!! $errors->first('promoter_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1 collapsible collapse @if (isset($event->soundcheck_at) || isset($event->door_at)) show @else hide @endif" id="form-time">
	<div class="form-group col-md-2">
		{!! Form::label('soundcheck_at','Soundcheck At:') !!}
		{!! Form::dateTimeLocal('soundcheck_at', (isset($series->soundcheck_at)) ?
		$series->soundcheck_at->format('Y-m-d\\TH:i') : NULL, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('soundcheck_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
		{!! Form::label('door_at','Door At:') !!}
		{!! Form::dateTimeLocal('door_at', (isset($action) && isset($series->door_at)) ?
		$series->door_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control form-background']) !!}
		{!! $errors->first('door_at','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-2">
	<div class="form-group col-md-2 {{$errors->has('start_at') ? 'has-error' : '' }}">
		{!! Form::label('start_at','Start At:') !!} <a href="#" class="float-end px-1"  title="Show additional time options"><i class="bi bi-clock-fill toggler" id="form-time-close-box" data-bs-target="#form-time" data-bs-toggle="collapse" aria-expanded="false" aria-controls="form-time" role="button"></i></a>
		{!! Form::dateTimeLocal('start_at', (isset($action) && isset($series->start_at)) ? $series->start_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control form-background']) !!}
		{!! $errors->first('start_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2 {{$errors->has('end_at') ? 'has-error' : '' }}">
		{!! Form::label('end_at','End At:') !!}
		{!! Form::dateTimeLocal('end_at', (isset($action) && isset($series->end_at)) ?
		$series->end_at->format('Y-m-d\\TH:i') : NULL, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('end_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2 {{$errors->has('length') ? 'has-error' : '' }}">
		{!! Form::label('length','Length (hours):') !!}
		{!! Form::text('length', null, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('length','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">

	<div class="form-group col-md-2">
		{!! Form::label('presale_price','Presale Price:') !!}
		{!! Form::text('presale_price', null, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('presale_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
		{!! Form::label('door_price','Door Price:') !!}
		{!! Form::text('door_price', null, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('door_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
		{!! Form::label('min_age','Min Age:') !!}
		{!! Form::select('min_age', [ '0' => 'All Ages', '18' => '18', '21' => '21'], (isset($series->min_age) ? $series->min_age : NULL), ['class' => 'form-select form-background']) !!}
		{!! $errors->first('min_age','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-2">
	<div class="form-group col-md-4">
		{!! Form::label('primary_link','Primary Link:') !!}
		{!! Form::text('primary_link', null, ['class' => 'form-control form-background', 'placeholder' => 'https://primarylink.com']) !!}
		{!! $errors->first('primary_link','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-4">
		{!! Form::label('ticket_link','Ticket Link:') !!}
		{!! Form::text('ticket_link', null, ['class' => 'form-control form-background', 'placeholder' => 'https://ticketlink.com',]) !!}
		{!! $errors->first('ticket_link','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
    <div class="form-group col-md-2">
        {!! Form::label('facebook_username','Facebook Username') !!}
        {!! Form::text('facebook_username', null, ['class' => 'form-control form-background']) !!}
        {!! $errors->first('facebook_username','<span class="help-block">:message</span>') !!}
    </div>

    <div class="form-group col-md-2">
        {!! Form::label('instagram_username','Instagram Username') !!}
        {!! Form::text('instagram_username', null, ['class' => 'form-control form-background']) !!}
        {!! $errors->first('instagram_username','<span class="help-block">:message</span>') !!}
    </div>

    <div class="form-group col-md-2">
        {!! Form::label('twitter_username','Twitter Username') !!}
        {!! Form::text('twitter_username', null, ['class' => 'form-control form-background']) !!}
        {!! $errors->first('twitter_username','<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-6">
		{!! Form::label('entity_list','Related Entities:') !!}
		{!! Form::select('entity_list[]', $entityOptions, null, ['id' => 'entity_list', 'class' =>'form-control
		select2',	'data-placeholder' => 'Choose a related artist, producer, dj', 'data-tags' =>'false', 'multiple']) !!}
		{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row mb-1">
	<div class="form-group col-md-6">
		{!! Form::label('tag_list','Tags:') !!}
		{!! Form::select('tag_list[]', $tagOptions, null, [
			'id' => 'tag_list',
			'class' => 'form-control select2 form-background',
			'data-placeholder' => 'Choose a keyword tagthat describes this event series',
			'data-tags' => 'false',
			'multiple' => 'true']) !!}
		{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
		{!! Form::label('visibility_id','Visibility:') !!}
		{!! Form::select('visibility_id', $visibilityOptions, (isset($series->visibility_id) ? $series->visibility_id :
		NULL),
		['class' => 'form-select form-background']) !!}
		{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
	
	<div class="form-group col-md-3">
		{!! Form::label('created_by','Owner:') !!}
		{!! Form::select('created_by', $userOptions, (isset($series->created_by) ? $series->created_by : NULL), ['class'
		=>'form-control select2', 'data-placeholder' => '']) !!}
		{!! $errors->first('created_by','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="form-group">

	@if (isset($eventLinkId))
	{!! Form::hidden('eventLinkId', $eventLinkId) !!}
	@endif
	{!! Form::submit(isset($action) && $action == 'update' ? 'Update Series' : 'Add Series', ['class' =>'btn btn-primary my-2']) !!}
</div>