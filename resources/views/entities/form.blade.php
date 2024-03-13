<div class="row mb-1">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-12">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' => 'form-control form-background', 'autofocus' => '']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-12">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Unique name for this entity (will validate)', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-12">
	{!! Form::label('short','Short description') !!}
	{!! Form::text('short', null, ['placeholder' => 'Add a brief description of this entity.','class' => 'form-control form-background']) !!}
	{!! $errors->first('short','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row mb-2">
	<div class="form-group col-md-12">
	{!! Form::label('description','In Depth') !!}
	{!! Form::textarea('description', null, ['placeholder' => 'Add a more in depth description here.','class' => 'form-control form-background']) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-2">
	<div class="form-group col-md-6">
	{!! Form::label('entity_type_id','Type') !!}
	{!! Form::select('entity_type_id', $entityTypeOptions, (isset($entity->entity_type_id) ? $entity->entity_type_id : NULL), ['class' => 'form-select form-background']) !!}
	{!! $errors->first('entity_type_id','<span class="help-block">:message</span>') !!}
	</div>


	<div class="form-group col-md-6">
	{!! Form::label('entity_status_id','Status') !!}
	{!! Form::select('entity_status_id', $entityStatusOptions, (isset($entity->entity_status_id) ? $entity->entity_status_id : NULL),['class' => 'form-select form-background']) !!}
	{!! $errors->first('entity_status_id','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row mb-1">
	<div class="form-group col-md-6">
		{!! Form::label('facebook_username','FB Username') !!}
		{!! Form::text('facebook_username', null, ['placeholder' => 'Add the related facebook username if there is one.','class' => 'form-control form-background']) !!}
		{!! $errors->first('facebook_username','<span class="help-block">:message</span>') !!}
	</div>

	<div class="form-group col-md-6">
		{!! Form::label('twitter_username','Twitter Username') !!}
		{!! Form::text('twitter_username', null, ['placeholder' => 'Add the related twitter username if there is one.','class' => 'form-control form-background']) !!}
		{!! $errors->first('twitter_username','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-6">
		{!! Form::label('instagram_username','Instagram Username') !!}
		{!! Form::text('instagram_username', null, ['placeholder' => 'Add the related instagram username if there is one.','class' => 'form-control form-background']) !!}
		{!! $errors->first('instagram_username','<span class="help-block">:message</span>') !!}
	</div>
	<div class="form-group col-md-6">
		{!! Form::label('started_at','Started At:') !!}
		{!! Form::dateTimeLocal('started_at', (isset($event->started_at)) ?
		$event->started_at->format('Y-m-d\\TH:i') : NULL, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('started_at','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-6">
	{!! Form::label('role_list','Roles:') !!}
	{!! Form::select('role_list[]', $roleOptions, null, ['id' => 'role_list',	'class' => 'form-control select2 form-background',
	'data-placeholder' => 'Choose a role',
	'data-tags' => 'false',
	'multiple']) !!}
	{!! $errors->first('roles','<span class="help-block">:message</span>') !!}
	</div>
	<div class="form-group col-md-6">
		{!! Form::label('tag_list','Tags:') !!}
		{!! Form::select('tag_list[]', $tagOptions, null, ['id' => 'tag_list', 'class' =>'form-control select2 form-background',
		'data-placeholder' => 'Choose a tag',
		'data-tags' => 'true',
		 'multiple']) !!}
		{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
		</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-6">
	{!! Form::label('alias_list','Aliases:') !!}
	{!! Form::select('alias_list[]', $aliasOptions, null, ['id' => 'alias_list',
		'class' =>'form-control select2 form-background',
		'data-placeholder' => 'Choose an alias',
		'data-tags' =>'true',
		'multiple'])
		 !!}
	{!! $errors->first('aliases','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row mb-1">
	<div class="form-group col-md-3">
		{!! Form::label('created_by','Owner:') !!}
		{!! Form::select('created_by', $userOptions, (isset($event->created_by) ? $event->created_by : NULL), ['class' =>'form-control select2' , 'data-placeholder' => '']) !!}
		{!! $errors->first('created_by','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Entity' : 'Add Entity',  ['class' =>'btn btn-primary my-2']) !!}
</div>
