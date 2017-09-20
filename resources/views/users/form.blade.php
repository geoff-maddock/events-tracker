<div class="form-group {{$errors->has('name') ? 'has-error' : '' }}">
{!! Form::label('first_name','First Name') !!}
{!! Form::text('first_name', null, ['class' =>'form-control']) !!}
{!! $errors->first('first_name','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group {{$errors->has('name') ? 'has-error' : '' }}">
{!! Form::label('last_name','Last Name') !!}
{!! Form::text('last_name', null, ['class' =>'form-control']) !!}
{!! $errors->first('last_name','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('alias','Alias') !!}
{!! Form::text('alias', null, ['class' =>'form-control']) !!}
{!! $errors->first('alias','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('bio','Bio') !!}
{!! Form::textarea('bio', null, ['class' =>'form-control']) !!}
{!! $errors->first('bio','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
    {!! Form::label('default_theme','Default Theme') !!}
    {!! Form::select('default_theme', Config::get('constants.themes'), (isset($user->profile->default_theme) ? $user->profile->default_theme : NULL), ['class' =>'form-control']) !!}
    {!! $errors->first('default_theme','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Profile' : 'Add Profile', null, ['class' =>'btn btn-primary']) !!}
</div>