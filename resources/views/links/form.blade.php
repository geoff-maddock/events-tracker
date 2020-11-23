<div class="row">
	<div class="form-group {{$errors->has('text') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('text','Text') !!}
	{!! Form::text('text', null, ['class' =>'form-control']) !!}
	{!! $errors->first('text','<span class="help-block">:message</span>') !!}
	</div>
</div>



<div class="row">
	<div class="form-group {{$errors->has('url') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('url','URL') !!}
	{!! Form::text('url', null, ['placeholder' => 'URL','class' =>'form-control']) !!}
	{!! $errors->first('url','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group {{$errors->has('title') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('title','Title') !!}
	{!! Form::text('title', null, ['placeholder' => 'Title text that will display when hovering over URL','class' =>'form-control']) !!}
	{!! $errors->first('title','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('is_primary') ? 'has-error' : '' }}">
	{!! Form::label('is_primary','Is Primary:') !!}
	{!! Form::checkbox('is_primary', isset($link->is_primary) ? $link->is_primary : 0, ['class' =>'form-control']) !!}
	{!! $errors->first('is_primary','<span class="help-block">:message</span>') !!}
	</div>
</div

<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Link' : 'Add Link', null, ['class' =>'btn btn-primary']) !!}
</div>
