@extends('layouts.app-tw')

@section('title', '419 - Page Expired')

{{--
    An error page is not a canonical resource: the layout's default would emit a
    self-referential <link rel="canonical"> for whatever bad URL was requested,
    which invites crawlers to index it. Claiming the section suppresses that
    fallback — the comment is load-bearing, since @hasSection compiles to a
    check for non-empty content, so an empty section would not override.
--}}
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
            <i class="bi bi-clock-history text-6xl text-muted-foreground"></i>
        </div>
        <h1 class="text-6xl font-bold text-foreground mb-4">419</h1>
        <p class="text-xl text-muted-foreground mb-8">Page expired</p>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
            Your session timed out. Please go back and try again.
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ URL::previous() }}" class="btn-secondary-tw">
                <i class="bi bi-arrow-left mr-2"></i>Go Back
            </a>
            <a href="{{ url('/') }}" class="btn-primary-tw">
                <i class="bi bi-house mr-2"></i>Home
            </a>
            <a href="{{ route('login') }}" class="btn-primary-tw">
                <i class="bi bi-box-arrow-in-right mr-2"></i>Log In
            </a>
        </div>
    </div>
</div>

@stop
