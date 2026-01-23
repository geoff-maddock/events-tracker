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
