<div class="form-group {{$errors->has('name') ? 'has-error' : '' }}">
{!! Form::label('first_name','First Name') !!}
{!! Form::text('first_name', null, ['class' => 'form-control form-background']) !!}
{!! $errors->first('first_name','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group {{$errors->has('name') ? 'has-error' : '' }}">
{!! Form::label('last_name','Last Name') !!}
{!! Form::text('last_name', null, ['class' => 'form-control form-background']) !!}
{!! $errors->first('last_name','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('alias','Alias') !!}
{!! Form::text('alias', null, ['class' => 'form-control form-background']) !!}
{!! $errors->first('alias','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group">
{!! Form::label('bio','Bio') !!}
{!! Form::textarea('bio', null, ['class' =>'form-control form-background']) !!}
{!! $errors->first('bio','<span class="help-block">:message</span>') !!}
</div>

<div class="row">
    <div class="form-group col-md-2">
        {!! Form::label('facebook_username','Facebook Username') !!}
        {!! Form::text('facebook_username', null, ['class' => 'form-control form-background']) !!}
        {!! $errors->first('facebook_username','<span class="help-block">:message</span>') !!}
    </div>

    <div class="form-group col-md-2">
        {!! Form::label('instagram_username','Instagram Username') !!}
        {!! Form::text('instagram_username', null, ['class' => 'form-control form-background']) !!}
        {!! $errors->first('instagram_username','<span class="help-block">:message</span>') !!}
    </div>

    <div class="form-group col-md-2">
        {!! Form::label('twitter_username','Twitter Username') !!}
        {!! Form::text('twitter_username', null, ['class' => 'form-control form-background']) !!}
        {!! $errors->first('twitter_username','<span class="help-block">:message</span>') !!}
    </div>
</div>

@can('grant_access')
<div class="row">
    <div class="form-group col-md-3 {{$errors->has('user_status_id') ? 'has-error' : '' }}">
        {!! Form::label('user_status_id', 'Status:') !!}
        {!! Form::select('user_status_id', $userStatusOptions, (isset($user->user_status_id) ? $user->user_status_id : NULL), ['class' => 'form-select form-background']) !!}
        {!! $errors->first('user_status_id','<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="row">
    <div class="form-group col-md-2">
        {!! Form::label('group_list','Group:') !!}
        {!! Form::select('group_list[]', $groupOptions, (isset($user->groups) ? $user->groups->pluck('id')->toArray() : NULL), ['id' => 'group_list', 'class' => 'form-select form-background select2',
         'data-placeholder' => 'Select group memberships',
         'data-tags' => 'false',
         'multiple']) !!}

        {!! $errors->first('groups','<span class="help-block">:message</span>') !!}
    </div>
</div>
@endcan


<div class="form-group col-md-2">
    {!! Form::label('default_theme', 'Default Theme') !!}
    {!! Form::select('default_theme', Config::get('constants.themes'), (isset($user->profile->default_theme) ? $user->profile->default_theme : NULL), ['class' => 'form-background form-select']) !!}
    {!! $errors->first('default_theme','<span class="help-block">:message</span>') !!}
</div>

<div class="row">
<div class="form-group col-md-2  {{$errors->has('setting_weekly_update') ? 'has-error' : '' }}">
    {!! Form::label('setting_weekly_update','Setting: Receive Weekly Updates') !!}
    {!! Form::checkbox('setting_weekly_update', (isset($user->profile->setting_weekly_update) ? $user->profile->setting_weekly_update : NULL)) !!}
    {!! $errors->first('setting_weekly_update','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group col-md-2  {{$errors->has('setting_daily_update') ? 'has-error' : '' }}">
    {!! Form::label('setting_daily_update','Setting: Receive Daily Updates') !!}
    {!! Form::checkbox('setting_daily_update', (isset($user->profile->setting_daily_update) ? $user->profile->setting_daily_update : NULL)) !!}
    {!! $errors->first('setting_daily_update','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group col-md-2  {{$errors->has('setting_instant_update') ? 'has-error' : '' }}">
    {!! Form::label('setting_instant_update','Setting: Receive Instant Updates') !!}
    {!! Form::checkbox('setting_instant_update', (isset($user->profile->setting_instant_update) ? $user->profile->setting_instant_update : NULL)) !!}
    {!! $errors->first('setting_instant_update','<span class="help-block">:message</span>') !!}
</div>

<div class="form-group col-md-2  {{$errors->has('setting_forum_update') ? 'has-error' : '' }}">
    {!! Form::label('setting_forum_update','Setting: Receive Forum Updates') !!}
    {!! Form::checkbox('setting_forum_update', (isset($user->profile->setting_forum_update) ? $user->profile->setting_forum_update : NULL)) !!}
    {!! $errors->first('setting_forum_update','<span class="help-block">:message</span>') !!}
</div>
</div>

<div class="form-group">
{!! Form::submit(isset($action) ? 'Update Profile' : 'Add Profile',  ['class' =>'btn btn-primary']) !!}
</div>