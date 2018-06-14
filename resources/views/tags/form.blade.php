<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
		{!! Form::label('name','Name') !!}
		{!! Form::text('name', null, ['class' =>'form-control']) !!}
		{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Tag' : 'Add Tag', null, ['class' =>'btn btn-primary']) !!}
</div>
