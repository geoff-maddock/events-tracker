@extends('layouts.app-tw')

@section('title', 'About')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground">About {{ config('app.app_name') }}</h1>
    </div>

    @if ($menu)
        <div class="space-y-6">
            @forelse ($menu->blogs as $blog)
                <div class="card-tw p-6">
                    @if ($blog->contentType && $blog->contentType->name === "HTML")
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $blog->body !!}
                        </div>
                    @else
                        <p class="text-foreground">{{ $blog->body }}</p>
                    @endif
                    <div class="mt-4 pt-4 border-t border-border">
                        <small class="text-muted-foreground">
                            <i class="bi bi-calendar mr-1"></i>{{ $blog->created_at->format('l F jS Y') }}
                        </small>
                    </div>
                </div>
            @empty
                <div class="card-tw p-8 text-center text-muted-foreground">
                    <i class="bi bi-info-circle text-4xl mb-3"></i>
                    <p>No content available yet.</p>
                </div>
            @endforelse
        </div>
    @else
        <div class="card-tw p-8 text-center text-muted-foreground">
            <i class="bi bi-exclamation-circle text-4xl mb-3"></i>
            <p>About page content not configured.</p>
        </div>
    @endif

    @include('partials.social-footer-tw')
</div>
@stop
