
<div class="row">
 
	<div class="form-group col-md-8 {{$errors->has('name') ? 'has-error' : '' }}">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' =>'form-control']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
 
	<div class="form-group col-md-8 {{$errors->has('slug') ? 'has-error' : '' }}">
	{!! Form::label('slug','Slug') !!}
	{!! Form::text('slug', null, ['placeholder' => 'Unique name for this thread (will validate)', 'class' =>'form-control']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
 
	<div class="form-group col-md-8">
	{!! Form::label('description','Description') !!}
	{!! Form::textarea('description', null, ['class' =>'form-control']) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
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

	<div class="form-group col-md-2 {{$errors->has('thread_type_id') ? 'has-error' : '' }}">
	{!! Form::label('thread_category_id','Thread category:') !!}
	{!! Form::select('thread_category_id', $threadCategories, (isset($thread->thread_category_id) ? $thread->thread_category_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('thread_category_id','<span class="help-block">:message</span>') !!}
	</div>


</div>


<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilities, (isset($thread->visibility_id) ? $thread->visibility_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>



<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('entity_list','Related Entities:') !!}
	{!! Form::select('entity_list[]', $entities, null, ['id' => 'entity_list', 'class' =>'form-control', 'multiple']) !!}
	{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('tag_list','Tags:') !!}
	{!! Form::select('tag_list[]', $tags, null, ['id' => 'tag_list', 'class' =>'form-control', 'multiple']) !!}
	{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="form-group">
{!! Form::hidden('forum_id', '1', array('id' => 'forum_id')) !!}
{!! Form::submit(isset($action) && $action == 'update' ? 'Update Thread' : 'Add Thread', null, ['class' =>'btn btn-primary']) !!}
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



		function handleError(error) {
			console.log('Error code:'+error.code);
			console.log(error.message);
		}

		function threadPhotos(thread){
			// get the photos from the api
		}
	</script>
@endsection