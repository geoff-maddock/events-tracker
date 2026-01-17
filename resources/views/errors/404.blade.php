@extends('layouts.app-tw')

@section('title', '404 - Page Not Found')

@section('content')

<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <div class="mb-8">
            <i class="bi bi-exclamation-triangle text-6xl text-muted-foreground"></i>
        </div>
        <h1 class="text-6xl font-bold text-foreground mb-4">404</h1>
        <p class="text-xl text-muted-foreground mb-8">Page not found</p>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ URL::previous() }}" class="btn-secondary-tw">
                <i class="bi bi-arrow-left mr-2"></i>Go Back
            </a>
            <a href="{{ url('/') }}" class="btn-primary-tw">
                <i class="bi bi-house mr-2"></i>Home
            </a>
        </div>
    </div>
</div>

@stop
