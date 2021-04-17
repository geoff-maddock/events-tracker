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

	<div class="form-group col-md-2 {{$errors->has('menu_parent_id') ? 'has-error' : '' }}">
		{!! Form::label('menu_parent_id', 'Parent:') !!}
		{!! Form::select('menu_parent_id', $menuOptions, (isset($menu->menu_parent_id) ? $menu->menu_parent_id : NULL), ['class' =>'form-control']) !!}
		{!! $errors->first('menu_parent_id','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
	<div class="form-permission col-md-4">
	{!! Form::label('body','Body') !!}
	{!! Form::textarea('body', null, ['placeholder' => 'Add a more in depth description here.','class' =>'form-control']) !!}
	{!! $errors->first('body','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
		{!! Form::label('visibility_id','Visibility:') !!}
		{!! Form::select('visibility_id', $visibilityOptions, (isset($menu->visibility_id) ? $menu->visibility_id : NULL), ['class' =>'form-control']) !!}
		{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>



<div class="form-permission">
{!! Form::submit(isset($action) ? 'Update Menu' : 'Add Menu',['class' =>'btn btn-primary']) !!}
</div>

