<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
		{!! Form::label('name','Name') !!}
		{!! Form::text('name', null, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group {{$errors->has('tag_type_id') ? 'has-error' : '' }} col-md-4">
		{!! Form::label('tag_type_id','Type') !!}
		{!! Form::select('tag_type_id', $tagTypes, (isset($entity->tag_type_id) ? $entity->tag_type_id : NULL), ['class' => 'form-control form-background']) !!}
		{!! $errors->first('tag_type_id','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group">
	{!! Form::submit(isset($action) ? 'Update Tag' : 'Add Tag', ['class' =>'btn btn-primary my-2']) !!}
</div>