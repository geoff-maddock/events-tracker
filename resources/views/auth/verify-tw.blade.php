@extends('layouts.app-tw')

@section('title', 'Verify Email')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-card rounded-lg border border-border shadow-sm p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto bg-primary/10 rounded-full flex items-center justify-center mb-4">
                    <i class="bi bi-envelope-check text-primary text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-foreground">Verify Your Email Address</h1>
            </div>

            @if (session('resent'))
                <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-check-circle text-green-500"></i>
                        <span class="text-green-500">A fresh verification link has been sent to your email address.</span>
                    </div>
                </div>
            @endif

            <div class="text-muted-foreground text-center space-y-4">
                <p>Before proceeding, please check your email for a verification link.</p>
                <p>
                    If you did not receive the email,
                    <a href="#" onclick="event.preventDefault(); document.getElementById('email-form').submit();" class="text-primary hover:underline font-medium">
                        click here to request another
                    </a>.
                </p>
            </div>

            <form id="email-form" action="{{ route('verification.resend') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>
</div>
@endsection
