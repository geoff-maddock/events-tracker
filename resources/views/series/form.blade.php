<div class="form-group {{$errors->has('name') ? 'has-error' : '' }}">
{!! Form::label('name','Name') !!}
{!! Form::text('name', null, ['class' =>'form-control']) !!}
{!! $errors->first('name','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('slug','Slug') !!}
{!! Form::text('slug', null, ['placeholder' => 'Unique name for this series (will validate)', 'class' =>'form-control']) !!}
{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('short','Short Description') !!}
{!! Form::text('short', null, ['class' =>'form-control']) !!}
{!! $errors->first('short','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('description','Description') !!}
{!! Form::textarea('description', null, ['class' =>'form-control']) !!}
{!! $errors->first('description','<span class="help-block">:message</span>') !!}
</div>

<div class="row">

	<div class="form-group col-md-2">
	{!! Form::label('founded_at','Founded At:') !!}
	{!! Form::dateTimeLocal('founded_at', (isset($series->founded_at)) ? $series->founded_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('founded_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('cancelled_at','Cancelled At:') !!}
	{!! Form::dateTimeLocal('cancelled_at', (isset($series->cancelled_at)) ? $series->cancelled_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('cancelled_at','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">

	<div class="form-group col-md-2 {{$errors->has('occurrence_type_id') ? 'has-error' : '' }}">
	{!! Form::label('occurrence_type_id','Occurrence type:') !!}
	{!! Form::select('occurrence_type_id', $occurrenceTypes, (isset($series->occurrence_type_id) ? $series->occurrence_type_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('occurrence_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('occurrence_week_id','Occurrence Week') !!}
	{!! Form::select('occurrence_week_id', $weeks, (isset($series->occurrence_week_id) ? $series->occurrence_week_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('occurrence_week_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('occurrence_day_id','Occurrence Day') !!}
	{!! Form::select('occurrence_day_id', $days, (isset($series->occurrence_day_id) ? $series->occurrence_day : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('occurrence_day_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('hold_date','Hold date') !!}
	{!! Form::checkbox('hold_date', (isset($series->hold_date) ? $series->hold_date : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('hold_date','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">

	<div class="form-group col-md-2 {{$errors->has('event_type_id') ? 'has-error' : '' }}">
	{!! Form::label('event_type_id','Event type:') !!}
	{!! Form::select('event_type_id', $eventTypes, (isset($series->event_type_id) ? $series->event_type_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('event_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('venue_id','Venue') !!}
	{!! Form::select('venue_id', $venues, (isset($series->venue_id) ? $series->venue_id : NULL), ['class' =>'form-control  select2']) !!}
	{!! $errors->first('venue_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('promoter_id','Promoter') !!}
	{!! Form::select('promoter_id', $promoters, (isset($series->promoter_id) ? $series->promoter_id : NULL), ['class' =>'form-control  select2']) !!}
	{!! $errors->first('promoter_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('soundcheck_at','Soundcheck At:') !!}
	{!! Form::dateTimeLocal('soundcheck_at', (isset($series->soundcheck_at)) ? $series->soundcheck_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('soundcheck_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('door_at','Door At:') !!}
	{!! Form::dateTimeLocal('door_at', (isset($action) && isset($series->door_at)) ? $series->door_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('door_at','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('start_at') ? 'has-error' : '' }}">
	{!! Form::label('start_at','Start At:') !!}
	{!! Form::dateTimeLocal('start_at', (isset($action) && isset($series->start_at)) ?  $series->start_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('start_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2 {{$errors->has('end_at') ? 'has-error' : '' }}">
	{!! Form::label('end_at','End At:') !!}
	{!! Form::dateTimeLocal('end_at', (isset($action) && isset($series->end_at)) ? $series->end_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('end_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2 {{$errors->has('length') ? 'has-error' : '' }}">
	{!! Form::label('length','Length (hours):') !!}
	{!! Form::text('length', '', ['class' =>'form-control']) !!}
	{!! $errors->first('length','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">


	<div class="form-group col-md-2">
	{!! Form::label('presale_price','Presale Price:') !!}
	{!! Form::text('presale_price', null, ['class' =>'form-control']) !!}
	{!! $errors->first('presale_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('door_price','Door Price:') !!}
	{!! Form::text('door_price', null, ['class' =>'form-control']) !!}
	{!! $errors->first('door_price','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('min_age','Min Age:') !!}
	{!! Form::select('min_age', [ '0' => 'All Ages', '18' => '18', '21' => '21'], (isset($event->min_age) ? $event->min_age : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('min_age','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('primary_link','Primary Link:') !!}
	{!! Form::text('primary_link', null, ['class' =>'form-control']) !!}
	{!! $errors->first('primary_link','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-4">
	{!! Form::label('ticket_link','Ticket Link:') !!}
	{!! Form::text('ticket_link', null, ['class' =>'form-control']) !!}
	{!! $errors->first('ticket_link','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilities, (isset($event->visibility_id) ? $event->visibility_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-6">
		{!! Form::label('entity_list','Related Entities:') !!}
		{!! Form::select('entity_list[]', $entities, null, ['id' => 'entity_list', 'class' =>'form-control select2', 'data-placeholder' => 'Choose a related artist, producer, dj', 'data-tags' =>'false', 'multiple']) !!}
		{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-6">
		{!! Form::label('tag_list','Tags:') !!}
		{!! Form::select('tag_list[]', $tags, old('tag_list'), ['id' => 'tag_list',
        'class' =>'form-control select2',
        'data-placeholder' => 'Choose a tag',
        'data-tags' =>'true',
        'multiple' => 'true']) !!}
		{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="form-group">
{!! Form::submit(isset($action) && $action == 'update' ? 'Update Series' : 'Add Series', null, ['class' =>'btn btn-primary']) !!}
</div>
