<div class="row">
 
	<div class="form-group col-md-8 {{$errors->has('name') ? 'has-error' : '' }}">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' => 'form-control form-background']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
 
	<div class="form-group col-md-8">
	{!! Form::label('description','Description') !!}
	{!! Form::textarea('description', null, ['class' => 'form-control form-background', 'cols' => 40, 'rows' => 2]) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
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

	<div class="form-group col-md-2 {{$errors->has('forum_id') ? 'has-error' : '' }}">
	{!! Form::label('forum_id','Forum:') !!}
	{!! Form::select('forum_id', $forumOptions, (isset($thread->forum_id) ? $thread->forum_id : NULL), ['class' => 'form-select form-background select2', 'data-placeholder' => 'Select a forum']) !!}
	{!! $errors->first('forum_id','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">

	<div class="form-group col-md-2 {{$errors->has('thread_type_id') ? 'has-error' : '' }}">
	{!! Form::label('thread_category_id','Thread category:') !!}
	{!! Form::select('thread_category_id', $threadCategoryOptions, (isset($thread->thread_category_id) ? $thread->thread_category_id : NULL), ['class' => 'form-select form-background']) !!}
	{!! $errors->first('thread_category_id','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilityOptions, (isset($thread->visibility_id) ? $thread->visibility_id : 3), ['class' => 'form-background form-select']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>



<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('entity_list','Related Entities:') !!}
	{!! Form::select('entity_list[]', $entityOptions, null, [
		'id' => 'entity_list',
	 	'class' => 'form-select form-background select2',
	 	'data-placeholder' =>'Choose a related artist, producer, dj',
	 	'data-tags' => 'false',
	  	'multiple'
		  ])
	!!}
	{!! $errors->first('entities','<span class="help-block">:message</span>') !!}
	</div>
</div>

<div class="row">

	<div class="form-group col-md-2 {{$errors->has('event_id') ? 'has-error' : '' }}">
	{!! Form::label('event_id','Event:') !!}
	{!! Form::select('event_id', $eventOptions, (isset($thread->event_id) ? $thread->event_id : NULL), ['class' =>'form-control select2', 'data-placeholder' => 'Select an event']) !!}
	{!! $errors->first('event_id','<span class="help-block">:message</span>') !!}
	</div>

</div>

<div class="row">
	<div class="form-group col-md-2">
	{!! Form::label('series_list','Related Series:') !!}
	{!! Form::select('series_list[]', $seriesOptions, isset($thread) ? $thread->series->pluck('id', 'id')->all() : NULL, ['id' => 'series_list', 'class' =>'form-control select2',
	 'data-placeholder' => 'Choose a related event series',
	 'data-tags' => 'false',
	  'multiple']) !!}
	{!! $errors->first('series','<span class="help-block">:message</span>') !!}
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
	{!! Form::submit(isset($action) && $action == 'update' ? 'Update Thread' : 'Add Thread', ['class' =>'btn btn-primary my-2']) !!}
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