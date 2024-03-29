<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' => 'form-control form-background']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group  {{$errors->has('slug') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Descriptive slug', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group  {{$errors->has('short') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('short','Short') !!}
	{!! Form::text('short', null, ['placeholder' => 'Short', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('short','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Entity Type' : 'Add Entity Type',  ['class' =>'btn btn-primary my-2']) !!}
</div>

