@extends('layouts.app-tw')

@section('title', 'Confirm Password')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="bg-card rounded-lg border border-border shadow-sm p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-foreground">Confirm Password</h1>
                <p class="text-muted-foreground mt-2">Please confirm your password before continuing.</p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
                @csrf

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

                <div class="flex items-center justify-between gap-4">
                    <x-ui.button type="submit" variant="default">
                        Confirm Password
                    </x-ui.button>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-primary hover:underline" href="{{ route('password.request') }}">
                            Forgot your password?
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
