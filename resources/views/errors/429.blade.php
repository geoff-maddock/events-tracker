@extends('layouts.app-tw')

@section('title', '429 - Too Many Requests')

{{-- see errors/404: error pages must not self-canonicalize or be indexed --}}
@section('canonical')
<!-- no canonical: error page -->
@endsection

@section('meta.robots')
<meta name="robots" content="noindex, follow">
@endsection

@section('content')

<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <div class="mb-8">
            <i class="bi bi-hourglass-split text-6xl text-muted-foreground"></i>
        </div>
        <h1 class="text-6xl font-bold text-foreground mb-4">429</h1>
        <p class="text-xl text-muted-foreground mb-8">Too many requests</p>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
            You've made a lot of requests in a short time. Please wait a minute and try again.
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
