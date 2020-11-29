<div class="row">
	<div class="form-permission {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' =>'form-control']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-permission col-md-4">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Descriptive slug', 'class' =>'form-control']) !!}
	{!! $errors->first('label','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-permission col-md-4">
	{!! Form::label('short','Short') !!}
	{!! Form::text('short', null, ['placeholder' => 'Short', 'class' =>'form-control']) !!}
	{!! $errors->first('label','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-permission">
{!! Form::submit(isset($action) ? 'Update Entity Type' : 'Add Entity Type', null, ['class' =>'btn btn-primary']) !!}
</div>

