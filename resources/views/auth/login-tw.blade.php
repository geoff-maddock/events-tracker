@extends('layouts.app-tw')

@section('title', 'Login')

@section('session-messages', 'inline')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-card rounded-lg border border-border shadow-sm p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-foreground">Welcome Back</h1>
                <p class="text-muted-foreground mt-2">Sign in to your account</p>
            </div>

            @include('partials.session-messages')

            @if (session('sign_in_reason'))
                <div class="mb-6 p-4 bg-primary/10 border border-primary/20 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-info-circle text-primary"></i>
                        <span class="text-foreground">{{ session('sign_in_reason') }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" class="space-y-6">
                @csrf

                <x-ui.form-group name="email" label="Email Address" :error="$errors->first('email')">
                    <x-ui.input
                        type="email"
                        name="email"
                        id="email"
                        :value="old('email')"
                        placeholder="Enter your email address"
                        :hasError="$errors->has('email')"
                        autocomplete="email"
                        autofocus
                        required />
                </x-ui.form-group>

                <x-ui.form-group name="password" label="Password" :error="$errors->first('password')">
                    <x-ui.input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Enter your password"
                        :hasError="$errors->has('password')"
                        autocomplete="current-password"
                        required />
                </x-ui.form-group>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" class="rounded border-border text-primary dark:bg-input dark:text-primary-foreground focus:ring-primary" {{ old('remember') ? 'checked' : '' }}>
                        <span class="text-muted-foreground">Remember me</span>
                    </label>
                    <a href="/password/reset" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>

                <x-ui.button type="submit" variant="default" class="w-full">
                    Sign In
                </x-ui.button>

                <p class="text-center text-sm text-muted-foreground">
                    Don't have an account?
                    <a href="{{ url('/register') }}" class="text-primary hover:underline font-medium">Register</a>
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts.footer')
<script src="{{ asset('/js/csrf-refresh.js') }}?v={{ @filemtime(public_path('js/csrf-refresh.js')) }}"></script>
@endsection
