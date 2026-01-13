@props([
    'hasError' => false,
])

@php
$classes = 'h-4 w-4 rounded-full border border-input bg-transparent text-primary shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
if ($hasError) {
    $classes .= ' border-destructive focus-visible:ring-destructive';
}
@endphp

<input type="radio" {{ $attributes->merge(['class' => $classes]) }} />
