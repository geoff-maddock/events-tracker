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

	<div class="form-group col-md-8">
		{!! Form::label('body','Body') !!}
		{!! Form::textarea('body', null, ['class' =>'form-control']) !!}
		{!! $errors->first('body','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">

	<div class="form-group col-md-2 {{$errors->has('menu_id') ? 'has-error' : '' }}">
		{!! Form::label('menu_id', 'Menu:') !!}
		{!! Form::select('menu_id', $menus, (isset($blog->menu_id) ?? NULL), ['class' =>'form-control']) !!}
		{!! $errors->first('menu_id','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('content_type_id') ? 'has-error' : '' }}">
		{!! Form::label('content_type_id','Content Type:') !!}
		{!! Form::select('content_type_id', $contentTypes, ($blog->content_type_id ?? NULL), ['class' =>'form-control']) !!}
		{!! $errors->first('content_type_id','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
		{!! Form::label('visibility_id','Visibility:') !!}
		{!! Form::select('visibility_id', $visibilities, ($blog->visibility_id ?? 3), ['class' =>'form-control']) !!}
		{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
		{!! Form::label('entity_list','Related Entities:') !!}
		{!! Form::select('entity_list[]', $entities, null, ['id' => 'entity_list',
         'class' =>'form-control select2',
         'data-placeholder' =>'Choose a related artist, producer, dj',
         'data-tags' => 'false',
          'multiple']) !!}
		{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
		{!! Form::label('tag_list','Tags:') !!}
		{!! Form::select('tag_list[]', $tags, null, ['id' => 'tag_list', 'class' =>'form-control select2',
        'data-placeholder' => 'Choose a tag',
        'data-tags' =>'true',
         'multiple']) !!}
		{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-permission">
{!! Form::submit(isset($action) ? 'Update Blog' : 'Add Blog', null, ['class' =>'btn btn-primary']) !!}
</div>

@section('footer')
	<script>
        // javascript to enable the select2 for the tag and entity list
        $('#tag_list').select2(
            {
                placeholder: 'Choose a tag',
                tags: true,
            });
        $('#entity_list').select2(
            {
                placeholder: 'Choose a related artist, producer, dj',
                tags: false,
            });
	</script>
@endsection