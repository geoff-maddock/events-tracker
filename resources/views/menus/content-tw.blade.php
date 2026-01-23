@extends('layouts.app-tw')

@section('title', $menu->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-3xl font-bold text-foreground">{{ $menu->name }}</h1>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <x-ui.button variant="secondary" href="{{ route('menus.index') }}">
                <i class="bi bi-arrow-left mr-2"></i>All Menus
            </x-ui.button>
        </div>
    </div>

    <div class="space-y-6">
        @forelse ($menu->blogs as $blog)
            <div class="card-tw p-6">
                @if ($blog->contentType->name === "HTML")
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
                <i class="bi bi-file-text text-4xl mb-3"></i>
                <p>No blog posts</p>
            </div>
        @endforelse
    </div>

    @include('partials.social-footer-tw')
</div>
@stop
