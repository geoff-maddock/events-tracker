@props([
    'hasError' => false,
])

@php
$classes = 'flex h-9 w-full rounded-md border border-input bg-input px-3 py-1 text-base shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
if ($hasError) {
    $classes .= ' border-destructive focus-visible:ring-destructive';
}
@endphp

<input {{ $attributes->merge(['class' => $classes]) }} />
