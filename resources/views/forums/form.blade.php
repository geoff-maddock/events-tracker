
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
	{!! Form::text('slug', null, ['placeholder' => 'Unique name for this event (will validate)', 'class' =>'form-control']) !!}
	{!! $errors->first('slug','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
 
	<div class="form-group col-md-8">
	{!! Form::label('description','Description') !!}
	{!! Form::textarea('description', null, ['class' =>'form-control', 'cols' => 2]) !!}
	{!! $errors->first('description','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">
 
	<div class="form-group col-md-8 {{$errors->has('sort-order') ? 'has-error' : '' }}">
	{!! Form::label('sort_order','Sort Order') !!}
	{!! Form::text('sort_order', null, ['class' =>'form-control']) !!}
	{!! $errors->first('sort_order','<span class="help-block">:message</span>') !!}
	</div>

</div>



<div class="row">
 
	<div class="form-group col-md-8 {{$errors->has('is_active') ? 'has-error' : '' }}">
	{!! Form::label('is_active','Active?') !!}
	{!! Form::checkbox('is_active', null, ['class' =>'form-control']) !!}
	{!! $errors->first('is_active','<span class="help-block">:message</span>') !!}
	</div>

</div>


<div class="row">
	<div class="form-group col-md-2 {{$errors->has('visibility_id') ? 'has-error' : '' }}">
	{!! Form::label('visibility_id','Visibility:') !!}
	{!! Form::select('visibility_id', $visibilities, (isset($forum->visibility_id) ? $forum->visibility_id : NULL), ['class' =>'form-control']) !!}
	{!! $errors->first('visibility_id','<span class="help-block">:message</span>') !!}
	</div>
</div>




<div class="form-group">

{!! Form::submit(isset($action) && $action == 'update' ? 'Update Forum' : 'Add Forum', null, ['class' =>'btn btn-primary']) !!}
</div>


@section('footer')
	<script>

		function handleError(error) {
			console.log('Error code:'+error.code);
			console.log(error.message);
		}


	</script>
@endsection