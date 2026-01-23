@extends('layouts.app-tw')

@section('title', '503 - Service Unavailable')

@section('content')

<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <div class="mb-8">
            <i class="bi bi-tools text-6xl text-muted-foreground"></i>
        </div>
        <h1 class="text-6xl font-bold text-foreground mb-4">503</h1>
        <p class="text-xl text-muted-foreground mb-8">Be right back</p>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
            We're currently performing maintenance. Please check back shortly.
        </p>
    </div>
</div>

@stop
