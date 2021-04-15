<div class="row">
	<div class="form-permission {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' =>'form-control']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-permission col-md-4">
	{!! Form::label('label','Label') !!}
	{!! Form::text('label', null, ['placeholder' => 'Descriptive label for the permission', 'class' =>'form-control']) !!}
	{!! $errors->first('label','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-permission col-md-4">
	{!! Form::label('level','Level') !!}
	{!! Form::text('level', null, ['placeholder' => 'Add the corresponding access level.','class' =>'form-control']) !!}
	{!! $errors->first('level','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-permission col-md-4">
	{!! Form::label('description','In Depth') !!}
	{!! Form::textarea('description', null, ['placeholder' => 'Add a more in depth description here.','class' =>'form-control']) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-permission col-md-2">
	{!! Form::label('group_list','Groups:') !!}
		{!! Form::select('group_list[]', $groupOptions, null, ['id' => 'group_list', 'class' =>'form-control select2',
'data-placeholder' => 'Choose a group',
'data-tags' =>'true',
 'multiple']) !!}

	{!! $errors->first('groups','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mt-2 mb-2">
	<div class="form-permission col-md-2">
	{!! Form::submit(isset($action) ? 'Update Permission' : 'Add Permission', ['class' =>'btn btn-primary']) !!}
	</div>
</div>

