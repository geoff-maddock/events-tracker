@extends('layouts.app-tw')

@section('title', 'Reset Password')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-card rounded-lg border border-border shadow-sm p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto bg-primary/10 rounded-full flex items-center justify-center mb-4">
                    <i class="bi bi-key text-primary text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-foreground">Reset Password</h1>
                <p class="text-muted-foreground mt-2">Enter your email to receive a reset link</p>
            </div>

            @if (session('status'))
                <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-check-circle text-green-500"></i>
                        <span class="text-green-500">{{ session('status') }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ url('/password/email') }}" class="space-y-6">
                @csrf

                <x-ui.form-group name="email" label="Email Address" :error="$errors->first('email')" required>
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

                <x-ui.button type="submit" variant="default" class="w-full">
                    Send Password Reset Link
                </x-ui.button>

                <p class="text-center text-sm text-muted-foreground">
                    Remember your password?
                    <a href="{{ url('/login') }}" class="text-primary hover:underline font-medium">Sign In</a>
                </p>
            </form>
        </div>
    </div>
</div>
@endsection
