<div class="row">
	<div class="form-group {{$errors->has('name') ? 'has-error' : '' }} col-md-4">
	{!! Form::label('name','Name') !!}
	{!! Form::text('name', null, ['class' => 'form-control form-background']) !!}
	{!! $errors->first('name','<span class="help-block">:message</span>') !!}
	</div>
</div>


<div class="row">

	<div class="form-group col-md-2 {{$errors->has('menu_id') ? 'has-error' : '' }}">
		{!! Form::label('forum_id', 'Forum:') !!}
		{!! Form::select('forum_id', $forumOptions, ($category->forum_id ?? NULL), ['class' => 'form-select form-background']) !!}
		{!! $errors->first('forum_id','<span class="help-block">:message</span>') !!}
	</div>

</div>


<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Category' : 'Add Category',  ['class' =>'btn btn-primary my-2']) !!}
</div>

@section('footer')
@endsection
