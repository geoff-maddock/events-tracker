<div class="form-group {{$errors->has('title') ? 'has-error' : '' }}">
{!! Form::label('title','Title') !!}
{!! Form::text('title', null, ['class' =>'form-control']) !!}
{!! $errors->first('title','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('body','Body') !!}
{!! Form::textarea('body', null, ['class' =>'form-control']) !!}
{!! $errors->first('body','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('published_at','Publish On:Body') !!}
{!! Form::input('date', 'published_at', date('Y-m-d'), ['class' =>'form-control']) !!}
</div>


<div class="form-group">
{!! Form::submit('Add Article', null, ['class' =>'btn btn-primary']) !!}
</div>
