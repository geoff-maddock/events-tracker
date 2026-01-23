@props([
    'entity',
    'context' => null,
    'variant' => 'primary'
])

@php
    // Determine the contextual filter route based on context
    $contextRoute = match($context) {
        'events' => route('events.relatedto', $entity->slug),
        'series' => url('/series/related-to/' . $entity->slug),
        'threads' => url('/threads/related-to/' . $entity->slug),
        'photos' => route('entities.show', $entity->slug), // No specific route for photos by entity
        'blogs' => route('entities.show', $entity->slug), // No specific route for blogs by entity
        default => route('entities.show', $entity->slug)
    };
    
    // Determine badge variant classes
    $variantClass = match($variant) {
        'primary' => 'badge-primary-tw',
        'accent' => 'badge-accent-tw',
        'secondary' => 'badge-secondary-tw',
        default => 'badge-primary-tw'
    };
@endphp

<span class="badge-tw {{ $variantClass }} text-xs inline-flex items-center gap-1 group">
    <a href="{{ route('entities.show', $entity->slug) }}" 
       class="hover:underline" 
       title="View {{ $entity->name }}">
        {{ $entity->name }}
    </a>
    @if ($context)
    <a href="{{ $contextRoute }}" 
       class="opacity-60 hover:opacity-100 transition-opacity" 
       title="Filter {{ $context }} by {{ $entity->name }}">
        <i class="bi bi-funnel text-xs"></i>
    </a>
    @else
    <i class="bi bi-box-arrow-up-right text-xs opacity-60"></i>
    @endif
</span>
