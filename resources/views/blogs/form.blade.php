<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' => 'form-control form-background']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group {{$errors->has('slug') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Descriptive slug', 'class' => 'form-control form-background']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">

	<div class="form-group col-md-8">
		{!! Form::label('body','Body') !!}
		{!! Form::textarea('body', null, ['class' => 'form-control form-background']) !!}
		{!! $errors->first('body','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">

	<div class="form-group col-md-2 {{$errors->has('menu_id') ? 'has-error' : '' }}">
		{!! Form::label('menu_id', 'Menu:') !!}
		{!! Form::select('menu_id', $menuOptions, ($blog->menu_id ?? NULL), ['class' => 'form-select form-background']) !!}
		{!! $errors->first('menu_id','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('content_type_id') ? 'has-error' : '' }}">
		{!! Form::label('content_type_id','Content Type:') !!}
		{!! Form::select('content_type_id', $contentTypeOptions, ($blog->contentType ? $blog->contentType->id : NULL), ['class' => 'form-select  form-background']) !!}
		{!! $errors->first('content_type_id','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
		{!! Form::label('visibility_id','Visibility:') !!}
		{!! Form::select('visibility_id', $visibilityOptions, ($blog->visibility ? $blog->visibility->id : null), ['class' => 'form-select form-background']) !!}
		{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
    <div class="form-group col-md-2 {{$errors->has('sort_order') ? 'has-error' : '' }}">
        {!! Form::label('sort_order','Sort Order:') !!}
        {!! Form::select('sort_order',[ 0 => 'Desc', 1 => 'Asc'], ($blog->sort_order ?? 0), ['class' => 'form-select form-background']) !!}
        {!! $errors->first('sort_order','<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="row">
	<div class="form-group col-md-2">
		{!! Form::label('entity_list','Related Entities:') !!}
		{!! Form::select('entity_list[]', $entityOptions, null, [
			'id' => 'entity_list',
         	'class' => ' form-background form-control select2',
         	'data-placeholder' =>'Choose a related artist, producer, dj',
         	'data-tags' => 'false',
          	'multiple']) 
		  !!}
		{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
		{!! Form::label('tag_list','Tags:') !!}
		{!! Form::select('tag_list[]', $tagOptions, null, ['id' => 'tag_list', 'class' =>'form-control select2',
        'data-placeholder' => 'Choose a tag',
        'data-tags' =>'true',
         'multiple']) !!}
		{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Blog' : 'Add Blog',  ['class' =>'btn btn-primary my-2']) !!}
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
