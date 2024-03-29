<div class="row">
 
	<div class="form-group col-md-8">
	{!! Form::label('body','Body') !!}
	{!! Form::textarea('body', null, ['class' => 'form-control form-background']) !!}
	{!! $errors->first('body','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('tag_list','Tags:') !!}
	{!! Form::select('tag_list[]', $tagOptions, null, ['id' => 'tag_list', 'class' => 'form-select form-background select2',
	'data-placeholder' => 'Choose a tag',
	'data-tags' =>'true',
	 'multiple']) !!}
	{!! $errors->first('tags','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilityOptions, (isset($post->visibility_id) ? $post->visibility_id : NULL), ['class' => 'form-select form-background']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="form-group">
{!! Form::hidden('thread_id', (isset($post) ? $post->thread_id : NULL), array('id' => (isset($post) ? $post->thread_id : NULL))) !!}
{!! Form::submit(isset($action) && $action == 'update' ? 'Update Post' : 'Add Post', ['class' =>'btn btn-primary my-2']) !!}
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