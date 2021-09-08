<div class="row">
	<div class="form-group {{$errors->has('message') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('message','Message') !!}
	{!! Form::textArea('message', null, ['class' =>'form-control form-background']) !!}
	{!! $errors->first('message','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group my-2">
	{!! Form::submit(isset($action) ? 'Update Comment' : 'Add Comment',  ['class' => 'btn btn-info']) !!}
</div>
