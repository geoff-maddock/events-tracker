<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-12">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' =>'form-control']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-12">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Unique name for this entity (will validate)', 'class' =>'form-control']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-12">
	{!! Form::label('short','Short description') !!}
	{!! Form::text('short', null, ['placeholder' => 'Add a brief description of this entity.','class' =>'form-control']) !!}
	{!! $errors->first('short','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-12">
	{!! Form::label('description','In Depth') !!}
	{!! Form::textarea('description', null, ['placeholder' => 'Add a more in depth description here.','class' =>'form-control']) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-6">
	{!! Form::label('entity_type_id','Type') !!}
	{!! Form::select('entity_type_id', $entityTypes, (isset($entity->entity_type_id) ? $entity->entity_type_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('entity_type_id','<span class="help-block">:message</span>') !!}
	</div>


	<div class="form-group col-md-6">
	{!! Form::label('entity_status_id','Status') !!}
	{!! Form::select('entity_status_id', $entityStatuses, (isset($entity->entity_status_id) ? $entity->entity_status_id : NULL),['class' =>'form-control']) !!}
	{!! $errors->first('entity_status_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-6">
	{!! Form::label('role_list','Roles:') !!}
	{!! Form::select('role_list[]', $roles, null, ['id' => 'role_list','class' =>'form-control', 'multiple']) !!}
	{!! $errors->first('roles','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-6">
	{!! Form::label('tag_list','Tags:') !!}
	{!! Form::select('tag_list[]', $tags, null, ['id' => 'tag_list','class' =>'form-control', 'multiple']) !!}
	{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Entity' : 'Add Entity', null, ['class' =>'btn btn-primary']) !!}
</div>


@section('footer')
	<script>
		$('#tag_list').select2(
			{
				placeholder: 'Choose a tag',
				tags: true,
			});
		$('#role_list').select2(
			{
				placeholder: 'Choose a role',
				tags: false,
			});
	</script>
@endsection