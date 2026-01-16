@extends('layouts.app-tw')

@section('title', 'Register')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="bg-card rounded-lg border border-border shadow-sm p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-foreground">Create Account</h1>
                <p class="text-muted-foreground mt-2">Join our community today</p>
            </div>

            @if (count($errors) > 0)
                <div class="mb-6 p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
                    <div class="flex items-start gap-3">
                        <i class="bi bi-exclamation-triangle text-destructive text-lg mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-destructive">There were some problems with your input.</p>
                            <ul class="mt-2 text-sm text-destructive/90 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-exclamation-triangle text-destructive"></i>
                        <span class="text-destructive">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ url('/register') }}" class="space-y-6">
                @csrf

                <x-ui.form-group name="name" label="Name" :error="$errors->first('name')" required>
                    <x-ui.input
                        type="text"
                        name="name"
                        id="name"
                        :value="old('name')"
                        placeholder="Enter your name"
                        :hasError="$errors->has('name')"
                        autocomplete="name"
                        autofocus
                        required />
                </x-ui.form-group>

                <x-ui.form-group name="email" label="Email Address" :error="$errors->first('email')" required>
                    <x-ui.input
                        type="email"
                        name="email"
                        id="email"
                        :value="old('email')"
                        placeholder="Enter your email address"
                        :hasError="$errors->has('email')"
                        autocomplete="email"
                        required />
                </x-ui.form-group>

                <x-ui.form-group name="password" label="Password" :error="$errors->first('password')" required>
                    <x-ui.input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Create a password"
                        :hasError="$errors->has('password')"
                        autocomplete="new-password"
                        required />
                </x-ui.form-group>

                <x-ui.form-group name="password_confirmation" label="Confirm Password" required>
                    <x-ui.input
                        type="password"
                        name="password_confirmation"
                        id="password-confirm"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        required />
                </x-ui.form-group>

                <div class="flex justify-center">
                    {!! NoCaptcha::display() !!}
                </div>

                <x-ui.button type="submit" variant="default" class="w-full">
                    Create Account
                </x-ui.button>

                <p class="text-center text-sm text-muted-foreground">
                    Already have an account?
                    <a href="{{ url('/login') }}" class="text-primary hover:underline font-medium">Sign In</a>
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts.footer')
{!! NoCaptcha::renderJs() !!}
@endsection
