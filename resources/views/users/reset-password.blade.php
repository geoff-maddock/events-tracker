@extends('app')

@section('title', 'Reset User Password')

@section('content')

<h1 class="display-crumbs text-primary">Reset Password for {{ $user->name }}</h1>

<div class="row">
    <div class="col-md-6">
        <div class="card surface">
            <div class="card-header bg-primary">
                <h5 class="my-0 fw-normal">Reset Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{!! route('users.resetPassword', ['id' => $user->id]) !!}">
                    @csrf

                    <div class="form-group {{$errors->has('password') ? 'has-error' : '' }}">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required>
                        {!! $errors->first('password','<span class="help-block form-error">:message</span>') !!}
                    </div>

                    <div class="form-group {{$errors->has('password_confirmation') ? 'has-error' : '' }}">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password" required>
                        {!! $errors->first('password_confirmation','<span class="help-block form-error">:message</span>') !!}
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                        <a href="{!! route('users.show', ['user' => $user->id]) !!}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@stop
