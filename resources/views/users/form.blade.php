<div class="form-group {{$errors->has('name') ? 'has-error' : '' }}">
{!! Form::label('name','Name') !!}
{!! Form::text('name', null, ['class' =>'form-control']) !!}
{!! $errors->first('name','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('slug','Slug') !!}
{!! Form::text('slug', null, ['class' =>'form-control']) !!}
{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('description','Description') !!}
{!! Form::textarea('description', null, ['class' =>'form-control']) !!}
{!! $errors->first('description','<span class="help-block">:message</span>') !!}
</div>


<div class="row">

	<div class="form-group col-md-2">
	{!! Form::label('event_type_id','Event type:') !!}
	{!! Form::select('event_type_id', $eventTypes, (isset($event->event_type_id) ? $event->event_type_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('event_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('venue_id','Venue') !!}
	{!! Form::select('venue_id', $venues, (isset($event->venue_id) ? $event->venue_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('venue_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('soundcheck_at','Soundcheck At:') !!}
	{!! Form::dateTimeLocal('soundcheck_at', (isset($event->soundcheck_at)) ? $event->soundcheck_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('soundcheck_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2"> 
	{!! Form::label('door_at','Door At:') !!}
	{!! Form::dateTimeLocal('door_at', (isset($action) && isset($event->door_at)) ? $event->door_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('door_at','<span class="help-block">:message</span>') !!}
	</div>


	<div class="form-group col-md-2">
	{!! Form::label('start_at','Start At:') !!}
	{!! Form::dateTimeLocal('start_at', (isset($action) && isset($event->start_at)) ?  $event->start_at->format('Y-m-d\\TH:i') : Carbon\Carbon::now()->format('Y-m-d\\TH:i'), ['class' =>'form-control']) !!}
	{!! $errors->first('start_at','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('end_at','End At:') !!}
	{!! Form::dateTimeLocal('end_at', (isset($action) && isset($event->end_at)) ? $event->end_at->format('Y-m-d\\TH:i') : NULL, ['class' =>'form-control']) !!}
	{!! $errors->first('end_at','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilities, (isset($event->visibility_id) ? $event->visibility_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('tag_list','Tags:') !!}
	{!! Form::select('tag_list[]', $tags, null, ['id' => 'tag_list', 'class' =>'form-control', 'multiple']) !!}
	{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('attending','Attending') !!}
	@if (isset($event))
	  {{ $event->attending }}
	@else
	  0
	@endif
	</div>
</div>


<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Event' : 'Add Event', null, ['class' =>'btn btn-primary']) !!}
</div>


@section('footer')
	<script>
		$('#tag_list').select2(
			{
				placeholder: 'Choose a tag',
				tags: true,
			});
	</script>
@endsection