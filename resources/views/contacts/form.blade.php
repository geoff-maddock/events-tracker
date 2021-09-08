<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' =>'form-control form-background']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('email','Email') !!}
	{!! Form::text('email', null, ['placeholder' => 'Email address','class' => 'form-control form-background']) !!}
	{!! $errors->first('email','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('phone','Phone') !!}
	{!! Form::text('phone', null, ['placeholder' => 'Phone #','class' => 'form-control form-background']) !!}
	{!! $errors->first('phone','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('other','Other') !!}
	{!! Form::text('other', null, ['placeholder' => 'Other','class' => 'form-control form-background']) !!}
	{!! $errors->first('other','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('type','Type') !!}
	{!! Form::text('type', null, ['placeholder' => 'Type of contact - agent, personal, management, baby\'s mamma','class' => 'form-control form-background']) !!}
	{!! $errors->first('type','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilities, (isset($event->visibility_id) ? $event->visibility_id : 3), ['class' => 'form-control form-background']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div

<div class="form-group">
	{!! Form::submit(isset($action) ? 'Update Contact' : 'Add Contact', ['class' =>'btn btn-primary my-2']) !!}
</div>
