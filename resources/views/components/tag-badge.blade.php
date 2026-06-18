@props([
    'tag',
    'context' => null,
    'variant' => 'secondary'
])

@php
    // Determine the contextual filter route based on context
    $contextRoute = match($context) {
        'events' => route('events.tag', $tag->slug),
        'entities' => route('entities.tag', $tag->slug),
        'series' => route('series.tag', $tag->slug),
        'threads' => route('threads.tag', $tag->slug),
        'posts' => route('posts.tag', $tag->slug),
        'photos' => route('photos.tag', $tag->slug),
        'calendar' => route('calendar.tag', $tag->slug),
        default => route('tags.show', $tag->slug)
    };
    
    // Determine badge variant classes
    $variantClass = match($variant) {
        'primary' => 'badge-primary-tw',
        'accent' => 'badge-accent-tw',
        'secondary' => 'badge-secondary-tw',
        default => 'badge-secondary-tw'
    };
@endphp

<span class="badge-tw {{ $variantClass }} text-xs inline-flex items-center gap-1 group">
    <a href="{{ $contextRoute }}"
       class="inline-flex items-center min-h-[24px] hover:underline"
       title="Filter {{ $context ?? 'items' }} by {{ $tag->name }}">
        {{ $tag->name }}
    </a>
    <a href="{{ route('tags.show', $tag->slug) }}"
       class="inline-flex items-center justify-center min-w-[24px] min-h-[24px] -mr-1 opacity-60 hover:opacity-100 transition-opacity"
       title="View tag details"
       aria-label="View details for {{ $tag->name }}">
        <i class="bi bi-info-circle text-xs" aria-hidden="true"></i>
    </a>
</span>
