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

@can('grant_access')
<div class="row">
    <div class="form-group col-md-3 {{$errors->has('user_status_id') ? 'has-error' : '' }}">
        {!! Form::label('user_status_id','Status:') !!}
        {!! Form::select('user_status_id', $userStatusOptions, (isset($user->user_status_id) ? $user->user_status_id : NULL), ['class' =>'form-control']) !!}
        {!! $errors->first('user_status_id','<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="row">
    <div class="form-group col-md-2">
        {!! Form::label('group_list','Group:') !!}
        {!! Form::select('group_list[]', $groupOptions, (isset($user->groups) ? $user->groups->pluck('id')->toArray() : NULL), ['id' => 'group_list', 'class' =>'form-control select2',
         'data-placeholder' => 'Select group memberships',
         'data-tags' => 'false',
         'multiple']) !!}

        {!! $errors->first('groups','<span class="help-block">:message</span>') !!}
    </div>
</div>
@endcan

<div class="form-group">
    {!! Form::label('default_theme','Default Theme') !!}
    {!! Form::select('default_theme', Config::get('constants.themes'), (isset($user->profile->default_theme) ? $user->profile->default_theme : NULL), ['class' =>'form-control']) !!}
    {!! $errors->first('default_theme','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Profile' : 'Add Profile',  ['class' =>'btn btn-primary']) !!}
</div>