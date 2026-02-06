@extends('layouts.app-tw')

@section('title', 'Reset User Password')

@section('content')

<div class="max-w-2xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-primary mb-2">Reset Password</h1>
        <p class="text-muted-foreground">{{ $user->name }}</p>
    </div>

    <!-- Action Menu -->
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('users.show', ['user' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
            <i class="bi bi-arrow-left mr-2"></i>
            Back to Profile
        </a>
    </div>

    <!-- Form Card -->
    <div class="card-tw">
        <div class="p-6">
            <form method="POST" action="{{ route('users.resetPassword', ['id' => $user->id]) }}">
                @csrf

                <!-- New Password -->
                <x-ui.form-group
                    name="password"
                    label="New Password"
                    :error="$errors->first('password')"
                    required>
                    <x-ui.input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Enter new password"
                        :hasError="$errors->has('password')"
                        required />
                </x-ui.form-group>

                <!-- Confirm Password -->
                <x-ui.form-group
                    name="password_confirmation"
                    label="Confirm Password"
                    :error="$errors->first('password_confirmation')"
                    required>
                    <x-ui.input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        placeholder="Confirm new password"
                        :hasError="$errors->has('password_confirmation')"
                        required />
                </x-ui.form-group>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                        <i class="bi bi-key mr-2"></i>
                        Reset Password
                    </button>
                    <a href="{{ route('users.show', ['user' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@stop
