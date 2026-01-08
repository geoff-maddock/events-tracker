@props([
    'hasError' => false,
])

@php
$classes = 'flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
if ($hasError) {
    $classes .= ' border-destructive focus-visible:ring-destructive';
}
@endphp

<textarea {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</textarea>
