<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' => 'form-control form-background']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('label','Label') !!}
	{!! Form::text('label', null, ['placeholder' => 'Descriptive label for the group', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('label','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('level','Level') !!}
	{!! Form::text('level', null, ['placeholder' => 'Add the corresponding access level.','class' => 'form-control form-background']) !!}
	{!! $errors->first('level','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-4">
	{!! Form::label('description','In Depth') !!}
	{!! Form::textarea('description', null, ['placeholder' => 'Add a more in depth description here.','class' => 'form-control form-background']) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('permission_list','Permission:') !!}
	{!! Form::select('permission_list[]', $permissionOptions, null,
	['id' => 'permission_list','class' =>'form-control select2', 'data-placeholder' => 'Select a related permission', 'data-tags' =>'false', 'multiple']) !!}
	{!! $errors->first('permissions','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-6">
		{!! Form::label('user_list','Users:') !!}
		{!! Form::select('user_list[]', $userOptions, null,
		['id' => 'user_list', 'class' =>'form-control select2', 'data-placeholder' => 'Select a related user', 'data-tags' =>'false', 'multiple']) !!}
		{!! $errors->first('users','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mt-2 mb-2">
	<div class="form-permission col-md-2">
	{!! Form::submit(isset($action) ? 'Update Group' : 'Add Group', ['class' =>'btn btn-primary']) !!}
	</div>
</div>