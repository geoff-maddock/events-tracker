<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' => 'form-control form-background']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group {{$errors->has('slug') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Unique name for this entity (will validate)', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('attn','ATTN') !!}
	{!! Form::text('attn', null, ['placeholder' => 'To the attention of.','class' => 'form-control form-background']) !!}
	{!! $errors->first('attn','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('address_one','Address Line One') !!}
	{!! Form::text('address_one', null, ['placeholder' => 'Address line one.','class' => 'form-control form-background']) !!}
	{!! $errors->first('address_one','<span class="help-block">:message</span>') !!}
	</div>

</div>
<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('address_two','Address Line Two') !!}
	{!! Form::text('address_two', null, ['placeholder' => 'Address line two','class' => 'form-control form-background']) !!}
	{!! $errors->first('address_two','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('neighborhood','Neighborhood') !!}
	{!! Form::text('neighborhood', null, ['placeholder' => 'Neighborhood','class' => 'form-control form-background']) !!}
	{!! $errors->first('neighborhood','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('city','City') !!}
	{!! Form::text('city', null, ['placeholder' => 'City','class' => 'form-control form-background']) !!}
	{!! $errors->first('city','<span class="help-block">:message</span>') !!}
	</div>


	<div class="form-group col-md-2">
	{!! Form::label('state','State') !!}
	{!! Form::text('state', null, ['placeholder' => 'State','class' => 'form-control form-background']) !!}
	{!! $errors->first('state','<span class="help-block">:message</span>') !!}
	</div>


	<div class="form-group col-md-2">
	{!! Form::label('postcode','Post Code') !!}
	{!! Form::text('postcode', null, ['placeholder' => 'Postal code','class' => 'form-control form-background']) !!}
	{!! $errors->first('postcode','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('country','Country') !!}
	{!! Form::text('country', null, ['placeholder' => 'Country', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('country','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('latitude','Latitude') !!}
	{!! Form::text('latitude', null, ['placeholder' => 'Latitude', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('latitude','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('longitude','Longitude') !!}
	{!! Form::text('longitude', null, ['placeholder' => 'Longitude', 'class' => 'form-control  form-background']) !!}
	{!! $errors->first('longitude','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('location_type_id','Type') !!}
	{!! Form::select('location_type_id', $locationTypeOptions, (isset($location->location_type_id) ? $location->location_type_id : NULL),['class' => 'form-control form-background']) !!}
	{!! $errors->first('location_type_id','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-2">
	{!! Form::label('capacity','Capacity') !!}
	{!! Form::text('capacity', null, ['placeholder' => 'Capacity','class' => 'form-control form-background']) !!}
	{!! $errors->first('capacity','<span class="help-block">:message</span>') !!}
	</div>

	<div class="row">
		<div class="form-group col-md-3 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
			{!! Form::label('visibility_id','Visibility:') !!}
			{!! Form::select('visibility_id', $visibilityOptions, (isset($event->visibility_id) ? $event->visibility_id : 3), ['class' => 'form-control form-background']) !!}
			{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
		</div>
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('map_url','Map URL') !!}
	{!! Form::text('map_url', null, ['placeholder' => 'Link to map','class' => 'form-control form-background']) !!}
	{!! $errors->first('map_url','<span class="help-block">:message</span>') !!}
	</div>
</div>



<div class="form-group my-2">
{!! Form::submit(isset($action) ? 'Update Location' : 'Add Location',  ['class' =>'btn btn-primary']) !!}
</div>
